<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\OtpSmsSender;
use App\Http\Controllers\Controller;
use App\Models\AdvocateProfile;
use App\Models\State;
use App\Models\User;
use App\Services\Msg91EmailService;
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
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Sign-up with a 6-digit code sent to the person's email (MSG91). Log in with the email code, or with a
 * mobile number and a 4-digit SMS code (AuthKey.io) for accounts that have one. Phone sign-up comes later.
 */
class UserRegistrationController extends Controller
{
    /** Length of the emailed code. */
    private const CODE_DIGITS = 6;

    /** How long an emailed code works. */
    private const CODE_MINUTES = 10;

    private const SUSPENDED_MESSAGE = 'This account is suspended. Write to support@vakilniti.com if you think this is a mistake.';

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

    /**
     * Whether this email may skip the emailed code. Only in local mode with the bypass switched on, and only for
     * emails listed in OTP_BYPASS_EMAILS ("*" lists everyone, for development on a private machine).
     */
    private function otpBypassEnabled(string $email): bool
    {
        if (! app()->environment('local') || ! config('services.otp_bypass_enabled', false)) {
            return false;
        }

        $allowed = (array) config('services.otp_bypass_emails', []);

        return in_array('*', $allowed, true) || in_array(Str::lower($email), $allowed, true);
    }

    /**
     * Make a code, email it, and keep only its hash. Returns null when sent, or a message for the person.
     */
    private function sendEmailCode(string $email, string $purpose, Msg91EmailService $mailer, ?int $userId = null, ?string $name = null): ?string
    {
        $key = $purpose.'-otp:'.$email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return 'Too many codes requested. Please try again later.';
        }

        $existing = DB::table('user_registration_otps')->where('email', $email)->first();

        if ($existing && $existing->last_sent_at && Carbon::parse($existing->last_sent_at)->addSeconds(60)->isFuture()) {
            return 'Please wait 60 seconds before asking for another code.';
        }

        $code = str_pad((string) random_int(0, 10 ** self::CODE_DIGITS - 1), self::CODE_DIGITS, '0', STR_PAD_LEFT);
        $deliveryError = $mailer->sendOtp($email, $code, $name, self::CODE_MINUTES);

        if ($deliveryError !== null) {
            return $deliveryError;
        }

        RateLimiter::hit($key, 3600);

        DB::table('user_registration_otps')->where('email', $email)->delete();
        DB::table('user_registration_otps')->insert([
            'user_id' => $userId,
            'email' => $email,
            'email_otp_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return null;
    }

    /**
     * Check a code typed for this email. Returns null when right (the code is used up), or an error message.
     */
    private function checkEmailCode(string $email, ?int $userId, string $code): ?string
    {
        $record = DB::table('user_registration_otps')
            ->where('email', $email)
            ->when($userId, fn ($query) => $query->where('user_id', $userId), fn ($query) => $query->whereNull('user_id'))
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast()) {
            return 'This code has expired. Ask for a new one.';
        }

        if ($record->attempts >= 5) {
            return 'Too many wrong tries. Ask for a new code.';
        }

        DB::table('user_registration_otps')->where('id', $record->id)->increment('attempts');

        if (! $record->email_otp_hash || ! Hash::check($code, $record->email_otp_hash)) {
            return 'That code is not right. Please check the email and try again.';
        }

        DB::table('user_registration_otps')->where('id', $record->id)->delete();

        return null;
    }

    /**
     * "98765 43210", "+91 98765-43210" → "+919876543210"; null when it is not an Indian mobile number.
     */
    private function normalizeMobile(string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        $digits = strlen($digits) === 12 && str_starts_with($digits, '91') ? substr($digits, 2) : $digits;

        return preg_match('/^[6-9][0-9]{9}$/', $digits) ? '+91'.$digits : null;
    }

    /**
     * Whether this number may skip the SMS code: local mode, bypass on, and listed in OTP_BYPASS_NUMBERS ("*" = all).
     */
    private function phoneBypassEnabled(string $mobile): bool
    {
        if (! app()->environment('local') || ! config('services.otp_bypass_enabled', false)) {
            return false;
        }

        $allowed = (array) config('services.otp_bypass_numbers', []);
        $lastTen = fn (string $number): string => substr(preg_replace('/\D+/', '', $number) ?? '', -10);

        return in_array('*', $allowed, true)
            || collect($allowed)->contains(fn (string $number): bool => $lastTen($number) !== '' && $lastTen($number) === $lastTen($mobile));
    }

    /**
     * Make a 4-digit code, send it by SMS, and keep only its hash. Returns null when sent, or a message for the person.
     */
    private function sendMobileCode(string $mobile, int $userId, OtpSmsSender $sms): ?string
    {
        $key = 'login-otp:'.$mobile;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return 'Too many codes requested. Please try again later.';
        }

        $existing = DB::table('user_registration_otps')->where('mobile', $mobile)->first();

        if ($existing && $existing->last_sent_at && Carbon::parse($existing->last_sent_at)->addSeconds(60)->isFuture()) {
            return 'Please wait 60 seconds before asking for another code.';
        }

        $code = (string) random_int(1000, 9999);

        if ($deliveryError = $sms->sendOtp($mobile, $code)) {
            return $deliveryError;
        }

        RateLimiter::hit($key, 3600);

        DB::table('user_registration_otps')->where('mobile', $mobile)->delete();
        DB::table('user_registration_otps')->insert([
            'user_id' => $userId,
            'mobile' => $mobile,
            'mobile_otp_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_MINUTES),
            'attempts' => 0,
            'last_sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return null;
    }

    /**
     * Check a code typed for this mobile number. Returns null when right (the code is used up), or an error message.
     */
    private function checkMobileCode(string $mobile, int $userId, string $code): ?string
    {
        $record = DB::table('user_registration_otps')->where('mobile', $mobile)->where('user_id', $userId)->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast()) {
            return 'This code has expired. Ask for a new one.';
        }

        if ($record->attempts >= 5) {
            return 'Too many wrong tries. Ask for a new code.';
        }

        DB::table('user_registration_otps')->where('id', $record->id)->increment('attempts');

        if (! $record->mobile_otp_hash || ! Hash::check($code, $record->mobile_otp_hash)) {
            return 'That code is not right. Please check the SMS and try again.';
        }

        DB::table('user_registration_otps')->where('id', $record->id)->delete();

        return null;
    }

    private function codeRules(): array
    {
        return ['otp' => ['required', 'digits:'.self::CODE_DIGITS]];
    }

    public function sendOtp(Request $request, Msg91EmailService $mailer): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'registration')
                ->withInput($request->only('email'))
                ->with('show_register_modal', true);
        }

        $email = Str::lower(trim($validator->validated()['email']));

        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'This email is already registered. Please log in.'], 'registration')
                ->withInput($request->only('email'))
                ->with('show_register_modal', true);
        }

        $pending = ['email' => $email];

        if ($this->otpBypassEnabled($email)) {
            $pending['otp_bypassed'] = true;
            $request->session()->forget('pending_user_registration');
            $request->session()->put('verified_registration', $pending);

            return redirect()->route('register.profile')
                ->with('status', 'Development mode: email code skipped.');
        }

        $error = $this->sendEmailCode($email, 'registration', $mailer);

        if ($error) {
            return back()->withErrors(['email' => $error], 'registration')
                ->withInput($request->only('email'))
                ->with('show_register_modal', true);
        }

        $request->session()->put('pending_user_registration', $pending);

        return redirect()->route('register.verify')
            ->with('status', 'We have emailed a 6-digit code to '.$email.'. It works for '.self::CODE_MINUTES.' minutes.');
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
        $validator = Validator::make($request->all(), $this->codeRules());

        if ($validator->fails()) {
            return back()->withErrors($validator, 'verification')->with('show_verify_modal', true);
        }

        $pending = $request->session()->get('pending_user_registration');

        if (! $pending || empty($pending['email'])) {
            return redirect()->route('register')
                ->withErrors(['registration' => 'Your registration session expired. Start again.'], 'registration')
                ->with('show_register_modal', true);
        }

        if ($error = $this->checkEmailCode($pending['email'], null, $request->string('otp')->toString())) {
            return back()->withErrors(['otp' => $error], 'verification')->with('show_verify_modal', true);
        }

        $request->session()->forget('pending_user_registration');
        $request->session()->put('verified_registration', $pending);

        return redirect()->route('register.profile');
    }

    public function resendOtp(Request $request, Msg91EmailService $mailer): RedirectResponse
    {
        $pending = $request->session()->get('pending_user_registration');

        if (! $pending || empty($pending['email'])) {
            return redirect()->route('register')
                ->withErrors(['registration' => 'Start registration again.'], 'registration')
                ->with('show_register_modal', true);
        }

        if ($error = $this->sendEmailCode($pending['email'], 'registration', $mailer)) {
            return back()->withErrors(['otp' => $error], 'verification')->with('show_verify_modal', true);
        }

        return back()
            ->with('status', 'A new code has been emailed to '.$pending['email'].'.')
            ->with('show_verify_modal', true);
    }

    public function showProfileSetup(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('verified_registration');

        if (! $pending || empty($pending['email'])) {
            return redirect()->route('register')
                ->withErrors(['registration' => 'Verify your email before completing registration.'], 'registration')
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

                ->withErrors(['registration' => 'Verify your email before completing registration.'], 'registration')

                ->with('show_register_modal', true);

        }

        $rules = [
            'account_role' => ['required', 'in:'.implode(',', array_keys(self::ACCOUNT_ROLES))],
            'onboarding_features' => ['required', 'array', 'min:1', 'max:'.count(self::ONBOARDING_FEATURES)],
            'onboarding_features.*' => ['required', 'distinct', 'in:'.implode(',', array_keys(self::ONBOARDING_FEATURES))],
            'onboarding_other' => ['nullable', 'string', 'max:500'],
            'full_name' => ['required', 'string', 'max:150'],
            // Optional for now: used for calls, client messages and team invites. Phone sign-in comes later.
            'mobile' => ['nullable', 'string', 'regex:/^\+91[6-9][0-9]{9}$/', 'unique:users,mobile'],
            // Set in the last sign-up step, so the advocate signs in with email and password (a code only as a fallback).
            'password' => ['required', 'string', 'confirmed', Password::min(8), 'max:72'],
        ];

        $accountRole = $request->input('account_role');
        $accountType = in_array($accountRole, self::COMPANY_ROLES, true)
            ? 'company'
            : 'individual';
        $selectedFeatures = (array) $request->input('onboarding_features', []);
        $mobileDigits = preg_replace('/\D+/', '', (string) $request->input('mobile')) ?? '';
        $request->merge(['mobile' => $mobileDigits === '' ? null : '+91'.substr($mobileDigits, -10)]);

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
            'mobile.regex' => 'Enter a 10-digit Indian mobile number, or leave it empty.',
            'mobile.unique' => 'Another account already uses this mobile number.',
            'password.required' => 'Choose a password to sign in with next time.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);
        try {

            $user = DB::transaction(function () use ($validated, $pending, $accountType): User {
                $user = User::create([
                    'name' => $validated['full_name'],
                    'email' => $pending['email'],
                    'mobile' => $validated['mobile'] ?? null,
                    // Without a chosen password the account gets an unusable random one until it sets its own.
                    'password' => Hash::make(filled($validated['password'] ?? null) ? $validated['password'] : Str::random(40)),
                    'password_set_at' => filled($validated['password'] ?? null) ? now() : null,
                    'email_verified_at' => ! ($pending['otp_bypassed'] ?? false) ? now() : null,
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

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        // New advocates go straight to bringing in their cases from eCourts.
        if ($accountType === 'individual' && $accountRole !== 'student_intern') {
            return redirect()->route('cases.find')
                ->with('auth_login_success', 'Your account is ready. Next, bring in your cases from eCourts, or skip this and go to Home.');
        }

        return redirect('/')
            ->with('auth_login_success', 'Your account is ready. Next time, sign in with your email and password.');

    }

    /**
     * Log in: with an email (a 6-digit code by email) or with a mobile number (a 4-digit code by SMS).
     */
    public function sendLoginOtp(Request $request, Msg91EmailService $mailer, OtpSmsSender $sms): RedirectResponse
    {
        if ($request->filled('mobile')) {
            return $this->sendPhoneLoginCode($request, $sms);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'login')
                ->withInput($request->only('email'))
                ->with('show_login_modal', true);
        }

        $email = Str::lower(trim($validator->validated()['email']));
        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No account uses this email. Check it, or create an account.'], 'login')
                ->withInput($request->only('email'))
                ->with('show_login_modal', true);
        }

        // A suspended account gets no code (and no email or SMS is sent).
        if ($user->suspended_at) {
            return back()->withErrors(['email' => self::SUSPENDED_MESSAGE], 'login')
                ->withInput($request->only('email'))
                ->with('show_login_modal', true);
        }

        if ($this->otpBypassEnabled($email)) {
            Auth::login($user, remember: true);
            $request->session()->regenerate();
            if (! $user->is_admin) {
                $request->session()->put('password_setup_proof', ['id' => $user->id, 'until' => now()->addMinutes(15)->timestamp]);
            }

            return redirect()->intended('/')
                ->with('auth_login_success', 'Development mode: signed in without the email code.');
        }

        if ($error = $this->sendEmailCode($email, 'login', $mailer, $user->id, $user->name)) {
            return back()->withErrors(['email' => $error], 'login')
                ->withInput($request->only('email'))
                ->with('show_login_modal', true);
        }

        $request->session()->forget('login_otp_mobile');
        $request->session()->put('login_otp_email', $email);

        return redirect()->route('login')
            ->with('status', 'We have emailed a 6-digit code to '.$email.'. It works for '.self::CODE_MINUTES.' minutes.')
            ->with('show_login_otp', true);
    }

    private function sendPhoneLoginCode(Request $request, OtpSmsSender $sms): RedirectResponse
    {
        $mobile = $this->normalizeMobile((string) $request->input('mobile'));
        $fail = fn (string $message): RedirectResponse => back()->withErrors(['mobile' => $message], 'login')
            ->withInput($request->only('mobile'))
            ->with('show_login_modal', true)
            ->with('login_via', 'mobile');

        if (! $mobile) {
            return $fail('Enter a valid Indian mobile number with 10 digits or +91 country code.');
        }

        $bypass = $this->phoneBypassEnabled($mobile);
        $user = User::where('mobile', $mobile)->when(! $bypass, fn ($query) => $query->whereNotNull('mobile_verified_at'))->first();

        if (! $user) {
            return $fail('No account was found for this number. Log in with your email instead.');
        }

        if ($user->suspended_at) {
            return $fail(self::SUSPENDED_MESSAGE);
        }

        if ($bypass) {
            Auth::login($user, remember: true);
            $request->session()->regenerate();

            return redirect()->intended('/')
                ->with('auth_login_success', 'Development mode: login successful without OTP.');
        }

        if ($error = $this->sendMobileCode($mobile, $user->id, $sms)) {
            return $fail($error);
        }

        $request->session()->forget('login_otp_email');
        $request->session()->put('login_otp_mobile', $mobile);

        return redirect()->route('login')
            ->with('status', 'A 4-digit code has been sent by SMS to '.$mobile.'.')
            ->with('show_login_otp', true);
    }

    public function verifyLoginOtp(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('login_otp_mobile');
        $email = $mobile ? null : $request->session()->get('login_otp_email');

        $validator = Validator::make($request->all(), ['otp' => ['required', 'digits:'.($mobile ? 4 : self::CODE_DIGITS)]]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'login')->with('show_login_otp', true);
        }

        $user = match (true) {
            (bool) $mobile => User::where('mobile', $mobile)->first(),
            (bool) $email => User::where('email', $email)->first(),
            default => null,
        };

        if (! $user || $user->suspended_at) {
            $request->session()->forget(['login_otp_email', 'login_otp_mobile']);

            return redirect()->route('login')
                ->withErrors(['otp' => $user ? self::SUSPENDED_MESSAGE : 'This code has expired. Ask for a new one.'], 'login')
                ->with('show_login_modal', true);
        }

        $code = $request->string('otp')->toString();

        if ($error = $mobile ? $this->checkMobileCode($mobile, $user->id, $code) : $this->checkEmailCode($email, $user->id, $code)) {
            return back()->withErrors(['otp' => $error], 'login')->with('show_login_otp', true);
        }

        $request->session()->forget(['login_otp_email', 'login_otp_mobile']);

        // The code reached this inbox or phone, so it is confirmed.
        $user->forceFill($mobile
            ? ['mobile_verified_at' => $user->mobile_verified_at ?? now()]
            : ['email_verified_at' => $user->email_verified_at ?? now()])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        // Signing in with a code proves the inbox or phone, so a password can be set or reset without the old one.
        $request->session()->put('signed_in_with_code_at', now()->timestamp);

        if (! $user->is_admin) {
            $request->session()->put('password_setup_proof', ['id' => $user->id, 'until' => now()->addMinutes(15)->timestamp]);
        }

        if (! $user->password_set_at && ! $user->is_admin) {
            return redirect()->to(route('profile.index').'#password')
                ->with('auth_login_success', 'Signed in. Set a password now, so next time you sign in with your email and password.');
        }

        return redirect()->intended('/')->with('auth_login_success', 'Login successful.');
    }

    /**
     * Sign in with the email and password. Five wrong tries for an email pause it for 15 minutes.
     */
    public function passwordLogin(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $fail = fn (string $message): RedirectResponse => back()->withErrors(['email' => $message], 'login')
            ->withInput($request->only('email', 'remember'))
            ->with('show_login_modal', true);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'login')->withInput($request->only('email', 'remember'))->with('show_login_modal', true);
        }

        $email = Str::lower(trim($validator->validated()['email']));
        $key = 'password-login:'.$email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $fail('Too many wrong passwords. Wait '.ceil(RateLimiter::availableIn($key) / 60).' minutes, or use "Email me a code".');
        }

        $user = User::where('email', $email)->first();

        if ($user?->suspended_at) {
            return $fail(self::SUSPENDED_MESSAGE);
        }

        if ($user && ! $user->password_set_at) {
            return $fail('This account has no password yet. Use "Email me a code", then set a password in Settings.');
        }

        if (! $user || ! Hash::check($validator->validated()['password'], $user->password)) {
            RateLimiter::hit($key, 15 * 60);

            return $fail('That email and password do not match.');
        }

        RateLimiter::clear($key);
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/')->with('auth_login_success', 'Login successful.');
    }

    /**
     * Back to the first step, to use a different email or number.
     */
    public function savePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('passwordSetup', [
            'current_password' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::min(8), 'max:72'],
        ]);
        // Accounts that signed up with an email code have no password to confirm yet.
        $isFirstPassword = ! $user->password_set_at && ! $user->is_admin;
        $proof = $request->session()->get('password_setup_proof');
        $verifiedRecently = ! $user->is_admin && ($proof['id'] ?? null) === $user->id
            && ($proof['until'] ?? 0) > now()->timestamp;
        if (! $isFirstPassword && ! $verifiedRecently
            && (! filled($data['current_password'] ?? null) || ! Hash::check($data['current_password'], $user->password))) {
            return redirect()->to(route('profile.index').'#password')->withErrors([
                'current_password' => 'Enter your current password. Forgot it? Sign out, use "Email me a code", then set a new one here.',
            ], 'passwordSetup');
        }

        $user->forceFill([
            'password' => Hash::make($data['password']), 'password_set_at' => now(),
        ] + ($isFirstPassword ? [] : [
            'remember_token' => Str::random(60), 'admin_access_version' => (int) $user->admin_access_version + 1,
        ]))->save();
        if (! $isFirstPassword && config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $user->id)
                ->where('id', '<>', $request->session()->getId())->delete();
        }
        $request->session()->forget($isFirstPassword ? ['password_setup_proof'] : ['password_setup_proof', 'admin_verified']);
        $request->session()->regenerate(true);
        // A changed password rotates the remember token, so renew this device's cookie and keep it signed in.
        Auth::login($user, remember: true);

        return redirect()->to(route('profile.index').'#password')->with('password_saved', 'Password saved. You can use it the next time you sign in.');
    }

    public function changeLoginEmail(Request $request): RedirectResponse
    {
        $request->session()->forget(['login_otp_email', 'login_otp_mobile']);

        return redirect()->route('login');
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
