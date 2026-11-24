<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdvocateProfile;
use App\Models\State;
use App\Models\User;
use App\Services\TwilioSmsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserRegistrationController extends Controller
{
    private const ACCOUNT_ROLES = [
        'independent_practitioner' => 'Independent practitioner',
        'senior_counsel' => 'Senior counsel',
        'advocate' => 'Junior to senior advocate',
        'student_intern' => 'Law student / Intern',
        'law_firm' => 'Law firm',
        'commercial_company' => 'Commercial company',
    ];

    private const COMPANY_ROLES = ['law_firm', 'commercial_company'];

    private const ONBOARDING_FEATURES = [
        'personalized_hearings' => 'Personalized court hearing lists',
        'daily_orders' => 'Daily orders and case details',
        'invoices_expenses_documents' => 'Invoices, expenses and document management',
        'judgment_research' => 'Judgment research',
        'mis_reports' => 'MIS reports',
        'other' => 'Other',
    ];

    public function showRegister(): View
    {

        return view('auth.user-register');

    }

    public function showLogin(): View
    {

        return view('auth.user-login');

    }

    private function normalizeMobile(string $mobile): string
    {

        $mobile = preg_replace('/[\s\\-\\(\\)]/', '', $mobile);

        if (preg_match('/^[6-9][0-9]{9}$/', $mobile)) {

            return '+91'.$mobile;

        }

        if (preg_match('/^\\+91[6-9][0-9]{9}$/', $mobile)) {

            return $mobile;

        }

        throw new \InvalidArgumentException(
            'Enter a valid Indian mobile number with 10 digits or +91 country code.'

        );

    }

    /**
     * Whether this number may skip the SMS code. Only in local mode with the bypass switched on, and only for
     * numbers listed in OTP_BYPASS_NUMBERS ("*" lists every number, for development on a private machine).
     * Any other number has to receive and enter the real SMS code.
     */
    private function otpBypassEnabled(string $mobile): bool
    {
        if (! app()->environment('local') || ! config('services.otp_bypass_enabled', false)) {
            return false;
        }

        $allowed = (array) config('services.otp_bypass_numbers', []);

        if (in_array('*', $allowed, true)) {
            return true;
        }

        $lastTen = fn (string $number): string => substr(preg_replace('/\D+/', '', $number) ?? '', -10);

        return collect($allowed)->contains(fn (string $number): bool => $lastTen($number) !== '' && $lastTen($number) === $lastTen($mobile));
    }

    private function sendMobileOtp(
        string $mobile,
        string $purpose,
        TwilioSmsService $smsService,
        ?int $userId = null
    ): ?string {
        $key = $purpose.'-otp:'.$mobile;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return 'Too many OTP requests. Please try again later.';
        }

        $existing = DB::table('user_registration_otps')
            ->where('mobile', $mobile)
            ->first();

        if (
            $existing &&
            $existing->last_sent_at &&
            Carbon::parse($existing->last_sent_at)->addSeconds(60)->isFuture()
        ) {
            return 'Please wait 60 seconds before requesting another OTP.';
        }

        $deliveryError = $smsService->startVerification($mobile);

        if ($deliveryError !== null) {
            return $deliveryError;
        }

        RateLimiter::hit($key, 3600);

        DB::table('user_registration_otps')
            ->where('mobile', $mobile)
            ->delete();

        DB::table('user_registration_otps')->insert([
            'user_id' => $userId,
            'mobile' => $mobile,
            'mobile_otp_hash' => null,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return null;
    }

    public function sendOtp(Request $request, TwilioSmsService $smsService): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'registration')
                ->withInput($request->only('mobile'))
                ->with('show_register_modal', true);
        }

        try {
            $mobile = $this->normalizeMobile($validator->validated()['mobile']);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['mobile' => $exception->getMessage()], 'registration')
                ->withInput($request->only('mobile'))
                ->with('show_register_modal', true);
        }

        if (User::where('mobile', $mobile)->exists()) {
            return back()->withErrors([
                'mobile' => 'This mobile number is already registered. Please log in.',
            ], 'registration')->withInput($request->only('mobile'))
                ->with('show_register_modal', true);
        }

        $pending = ['mobile' => $mobile];

        if ($this->otpBypassEnabled($mobile)) {
            $pending['otp_bypassed'] = true;
            $request->session()->forget('pending_user_registration');
            $request->session()->put('verified_registration', $pending);

            return redirect()->route('register.profile')
                ->with('status', 'Development mode: mobile OTP verification skipped.');
        }

        $error = $this->sendMobileOtp($mobile, 'registration', $smsService);

        if ($error) {
            return back()->withErrors(['mobile' => $error], 'registration')
                ->withInput($request->only('mobile'))
                ->with('show_register_modal', true);
        }

        $request->session()->put('pending_user_registration', $pending);

        return redirect()->route('register.verify')
            ->with('status', 'A four-digit OTP has been sent to your mobile number.');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {

        if (! $request->session()->has('pending_user_registration')) {

            return redirect()->route('register')

                ->withErrors(['registration' => 'Start registration first.'], 'registration')

                ->with('show_register_modal', true);

        }

        return view('auth.user-verify');

    }

    public function verifyOtp(Request $request): RedirectResponse
    {

        $validator = Validator::make($request->all(), [

            'otp' => ['required', 'digits:4'],

        ]);

        if ($validator->fails()) {

            return back()

                ->withErrors($validator, 'verification')

                ->with('show_verify_modal', true);

        }

        $pending = $request->session()->get('pending_user_registration');

        if (! $pending) {

            return redirect()->route('register')

                ->withErrors(['registration' => 'Your registration session expired. Start again.'], 'registration')

                ->with('show_register_modal', true);

        }

        $record = DB::table('user_registration_otps')

            ->where('mobile', $pending['mobile'])

            ->whereNull('user_id')

            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast()) {

            return redirect()->route('register')

                ->withErrors(['otp' => 'OTP expired. Please start registration again.'], 'registration')

                ->with('show_register_modal', true);

        }

        if ($record->attempts >= 5) {

            return back()

                ->withErrors(['otp' => 'Maximum verification attempts reached. Start registration again.'], 'verification')

                ->with('show_verify_modal', true);

        }

        DB::table('user_registration_otps')->where('id', $record->id)->increment('attempts');

        if (! $record->mobile_otp_hash || ! Hash::check($request->string('otp')->toString(), $record->mobile_otp_hash)) {

            return back()

                ->withErrors(['otp' => 'Incorrect OTP. Please try again.'], 'verification')

                ->with('show_verify_modal', true);

        }

        DB::table('user_registration_otps')->where('id', $record->id)->delete();

        $request->session()->forget('pending_user_registration');

        $request->session()->put('verified_registration', $pending);

        return redirect()->route('register.profile');

    }

    public function resendOtp(Request $request, TwilioSmsService $smsService): RedirectResponse
    {

        $pending = $request->session()->get('pending_user_registration');

        if (! $pending) {

            return redirect()->route('register')

                ->withErrors(['registration' => 'Start registration again.'], 'registration')

                ->with('show_register_modal', true);

        }

        $error = $this->sendMobileOtp($pending['mobile'], 'registration', $smsService);

        if ($error) {

            return back()

                ->withErrors(['otp' => $error], 'verification')

                ->with('show_verify_modal', true);

        }

        return back()

            ->with('status', 'A new OTP has been sent to your mobile number.')

            ->with('show_verify_modal', true);

    }

    public function showProfileSetup(Request $request): View|RedirectResponse
    {

        $pending = $request->session()->get('verified_registration');

        if (! $pending) {

            return redirect()->route('register')

                ->withErrors(['registration' => 'Verify your mobile number before completing registration.'], 'registration')

                ->with('show_register_modal', true);

        }

        return view('auth.user-profile-setup', [

            'pending' => $pending,

            'specializations' => AdvocateProfile::SPECIALIZATIONS,
            'accountRoles' => self::ACCOUNT_ROLES,
            'onboardingFeatures' => self::ONBOARDING_FEATURES,
            'stateGroups' => State::dropdownGroups(),

        ]);

    }

    public function completeProfile(Request $request): RedirectResponse
    {

        $pending = $request->session()->get('verified_registration');

        if (! $pending) {

            return redirect()->route('register')

                ->withErrors(['registration' => 'Verify your mobile number before completing registration.'], 'registration')

                ->with('show_register_modal', true);

        }

        $rules = [
            'account_role' => ['required', 'in:'.implode(',', array_keys(self::ACCOUNT_ROLES))],
            'onboarding_features' => ['required', 'array', 'min:1', 'max:'.count(self::ONBOARDING_FEATURES)],
            'onboarding_features.*' => ['required', 'distinct', 'in:'.implode(',', array_keys(self::ONBOARDING_FEATURES))],
            'onboarding_other' => ['nullable', 'string', 'max:500'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];

        $accountRole = $request->input('account_role');
        $accountType = in_array($accountRole, self::COMPANY_ROLES, true)
            ? 'company'
            : 'individual';
        $selectedFeatures = (array) $request->input('onboarding_features', []);

        if (in_array('other', $selectedFeatures, true)) {
            $rules['onboarding_other'] = ['required', 'string', 'max:500'];
        }

        if ($accountType === 'individual') {
            $isStudent = $accountRole === 'student_intern';

            $request->merge(AdvocateProfile::normaliseBarInput($request->all()));
            $rules += AdvocateProfile::barRegistrationRules(! $isStudent);
        } else {
            $gstin = strtoupper(trim((string) $request->input('gstin')));
            $request->merge(['gstin' => $gstin === '' ? null : $gstin]);

            $rules += [
                'firm_name' => ['required', 'string', 'max:255'],
                'proprietor_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:5000'],
                'city' => ['required', 'string', 'max:255'],
                'district' => ['required', 'string', 'max:255'],
                'region' => ['required', 'string', Rule::in(array_keys(State::indianStatesAndUnionTerritories()))],
                'pincode' => ['nullable', 'regex:/^[0-9]{6}$/'],
                'gstin' => [
                    'nullable',
                    'string',
                    'size:15',
                    'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
                ],
                'firm_email' => ['nullable', 'email', 'max:255'],
                'firm_phone' => ['nullable', 'string', 'max:20'],
            ];
        }

        $validated = $request->validate($rules, AdvocateProfile::barRegistrationMessages() + [
            'region.in' => 'Select a State or Union Territory from the list.',
        ]);
        try {

            $user = DB::transaction(function () use ($validated, $pending, $accountType): User {
                $user = User::create([
                    'name' => $validated['full_name'],
                    'email' => Str::lower($validated['email']),
                    'mobile' => $pending['mobile'],
                    'password' => Hash::make(Str::random(64)),
                    'mobile_verified_at' => ! ($pending['otp_bypassed'] ?? false) ? now() : null,
                    'account_type' => $accountType,
                    'account_role' => $validated['account_role'],
                    'onboarding_features' => $validated['onboarding_features'],
                    'onboarding_other' => $validated['onboarding_other'] ?? null,
                ]);

                if ($accountType === 'individual') {
                    DB::table('advocate_profiles')->insert([
                        'user_id' => $user->id,
                        'bar_council_registration_number' => AdvocateProfile::composeBarRegistration(
                            $validated['bar_state_code'] ?? null,
                            $validated['bar_number'] ?? null,
                            isset($validated['bar_registration_year']) ? (int) $validated['bar_registration_year'] : null,
                        ),
                        'bar_state' => $validated['bar_state'] ?? null,
                        'bar_state_code' => $validated['bar_state_code'] ?? null,
                        'bar_number' => $validated['bar_number'] ?? null,
                        'bar_registration_year' => $validated['bar_registration_year'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('firm_profiles')->insert([
                        'user_id' => $user->id,
                        'firm_name' => $validated['firm_name'],
                        'proprietor_name' => $validated['proprietor_name'] ?? null,
                        'address' => $validated['address'] ?? null,
                        'city' => $validated['city'],
                        'district' => $validated['district'] ?? null,
                        'state' => $validated['region'],
                        'state_code' => State::GST_STATE_CODES[$validated['region']],
                        'pincode' => $validated['pincode'] ?? null,
                        'gstin' => $validated['gstin'] ?? null,
                        'email' => isset($validated['firm_email']) ? Str::lower($validated['firm_email']) : null,
                        'phone' => $validated['firm_phone'] ?? null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $user;

            });

        } catch (\Throwable $exception) {

            throw $exception;
        }

        $request->session()->forget('verified_registration');

        Auth::login($user);

        $request->session()->regenerate();

        // New advocates go straight to bringing in their cases from eCourts.
        if ($accountType === 'individual' && $accountRole !== 'student_intern') {
            return redirect()->route('cases.find')
                ->with('auth_login_success', 'Your account is ready. Next, bring in your cases from eCourts, or skip this and go to Home.');
        }

        return redirect('/')
            ->with('auth_login_success', 'Your account is ready. You can now sign in with a phone OTP.');

    }

    public function sendLoginOtp(
        Request $request,
        TwilioSmsService $smsService
    ): RedirectResponse {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'login')
                ->withInput($request->only('mobile'))
                ->with('show_login_modal', true);
        }

        try {
            $mobile = $this->normalizeMobile($validator->validated()['mobile']);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['mobile' => $exception->getMessage()], 'login')
                ->withInput($request->only('mobile'))
                ->with('show_login_modal', true);
        }

        $userQuery = User::where('mobile', $mobile);

        if (! $this->otpBypassEnabled($mobile)) {
            $userQuery->whereNotNull('mobile_verified_at');
        }

        $user = $userQuery->first();

        if (! $user) {
            return back()->withErrors([
                'mobile' => 'No account was found for this number. Create an account first.',
            ], 'login')->withInput($request->only('mobile'))
                ->with('show_login_modal', true);
        }

        if ($this->otpBypassEnabled($mobile)) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/')
                ->with('auth_login_success', 'Development mode: login successful without OTP.');
        }

        $error = $this->sendMobileOtp($mobile, 'login', $smsService, $user->id);

        if ($error) {
            return back()->withErrors(['mobile' => $error], 'login')
                ->withInput($request->only('mobile'))
                ->with('show_login_modal', true);
        }

        $request->session()->put('login_otp_mobile', $mobile);

        return redirect()->route('login')
            ->with('status', 'A four-digit OTP has been sent to your mobile number.')
            ->with('show_login_otp', true);
    }

    public function verifyLoginOtp(Request $request): RedirectResponse
    {

        $validator = Validator::make($request->all(), [

            'otp' => ['required', 'digits:4'],

        ]);

        if ($validator->fails()) {

            return back()

                ->withErrors($validator, 'login')

                ->with('show_login_otp', true);

        }

        $mobile = $request->session()->get('login_otp_mobile');

        $user = $mobile

            ? User::where('mobile', $mobile)->whereNotNull('mobile_verified_at')->first()

            : null;

        $record = $mobile

            ? DB::table('user_registration_otps')

                ->where('mobile', $mobile)

                ->where('user_id', $user?->id)

                ->first()

            : null;

        if (! $user || ! $record || Carbon::parse($record->expires_at)->isPast()) {

            $request->session()->forget('login_otp_mobile');

            return redirect()->route('login')

                ->withErrors(['otp' => 'OTP expired. Request a new login OTP.'], 'login')

                ->with('show_login_modal', true);

        }

        if ($record->attempts >= 5) {

            return back()

                ->withErrors(['otp' => 'Maximum verification attempts reached. Request a new OTP.'], 'login')

                ->with('show_login_otp', true);

        }

        DB::table('user_registration_otps')->where('id', $record->id)->increment('attempts');

        if (! $record->mobile_otp_hash || ! Hash::check($request->string('otp')->toString(), $record->mobile_otp_hash)) {

            return back()

                ->withErrors(['otp' => 'Incorrect OTP. Please try again.'], 'login')

                ->with('show_login_otp', true);

        }

        DB::table('user_registration_otps')->where('id', $record->id)->delete();

        $request->session()->forget('login_otp_mobile');

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended('/')

            ->with('auth_login_success', 'Login successful.');

    }

    public function logout(Request $request): RedirectResponse
    {

        if (Auth::user()?->is_admin) {

            return redirect()->route('admin.login')

                ->with('status', 'Use the administrator logout option.');

        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');

    }
}
