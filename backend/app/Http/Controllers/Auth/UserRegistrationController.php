<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserRegistrationController extends Controller
{
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
        $mobile = preg_replace('/[\s\-\(\)]/', '', $mobile);

        if (preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
            return '+91' . $mobile;
        }

        if (preg_match('/^\+91[6-9][0-9]{9}$/', $mobile)) {
            return $mobile;
        }

        throw new \InvalidArgumentException(
            'Enter a valid Indian mobile number with 10 digits or +91 country code.'
        );
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'channel' => ['required', 'in:email,mobile'],
            'email' => ['nullable', 'required_if:channel,email', 'email', 'max:255'],
            'mobile' => ['nullable', 'required_if:channel,mobile', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'registration')
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('show_register_modal', true);
        }

        $validated = $validator->validated();

        try {
            $mobile = $validated['channel'] === 'mobile'
                ? $this->normalizeMobile($validated['mobile'])
                : null;
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['mobile' => $e->getMessage()], 'registration')
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('show_register_modal', true);
        }

        $email = $validated['channel'] === 'email'
            ? Str::lower(trim($validated['email']))
            : null;

        if ($email && User::where('email', $email)->exists()) {
            return back()
                ->withErrors(['email' => 'This email address is already registered.'], 'registration')
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('show_register_modal', true);
        }

        if ($mobile && User::where('mobile', $mobile)->exists()) {
            return back()
                ->withErrors(['mobile' => 'This mobile number is already registered.'], 'registration')
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('show_register_modal', true);
        }

        $key = 'registration-otp:' . ($email ?: $mobile);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors([
                    'identifier' => 'Too many OTP requests. Please try again later.'
                ], 'registration')
                ->with('show_register_modal', true);
        }

        $existing = DB::table('user_registration_otps')
            ->where(function ($query) use ($email, $mobile) {
                if ($email) {
                    $query->where('email', $email);
                } else {
                    $query->where('mobile', $mobile);
                }
            })
            ->first();

        if ($existing && $existing->last_sent_at &&
            Carbon::parse($existing->last_sent_at)->addSeconds(60)->isFuture()) {
            return back()
                ->withErrors([
                    'identifier' => 'Please wait 60 seconds before requesting another OTP.'
                ], 'registration')
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('show_register_modal', true);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            if ($email) {
                $mailer = config('mail.default');

                if ($mailer === 'log' || $mailer === 'array' ||
                    empty(config('mail.mailers.smtp.username')) ||
                    empty(config('mail.mailers.smtp.password'))) {
                    return back()
                        ->withErrors([
                            'email' => 'Email OTP delivery is not configured. Configure SMTP before registering.'
                        ], 'registration')
                        ->withInput($request->except('password', 'password_confirmation'))
                        ->with('show_register_modal', true);
                }

                Mail::raw(
                    "Your VakilNeeti registration OTP is: {$otp}\n\nThis OTP expires in 10 minutes. Do not share it with anyone.",
                    function ($message) use ($email) {
                        $message->to($email)->subject('VakilNeeti registration verification');
                    }
                );
            } else {
                $sid = config('vakilneeti.twilio_sid');
                $token = config('vakilneeti.twilio_token');
                $from = config('vakilneeti.twilio_from');

                if (!$sid || !$token || !$from) {
                    return back()
                        ->withErrors([
                            'mobile' => 'SMS OTP delivery is not configured. Configure the SMS provider before registering.'
                        ], 'registration')
                        ->withInput($request->except('password', 'password_confirmation'))
                        ->with('show_register_modal', true);
                }

                $response = Http::asForm()
                    ->withBasicAuth($sid, $token)
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                        'From' => $from,
                        'To' => $mobile,
                        'Body' => "Your VakilNeeti registration OTP is {$otp}. It expires in 10 minutes. Do not share it."
                    ]);

                if (!$response->successful()) {
                    return back()
                        ->withErrors([
                            'mobile' => 'The SMS provider could not send the OTP. Check your SMS configuration.'
                        ], 'registration')
                        ->withInput($request->except('password', 'password_confirmation'))
                        ->with('show_register_modal', true);
                }
            }
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors([
                    'identifier' => 'OTP delivery failed. Check your email or SMS provider configuration.'
                ], 'registration')
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('show_register_modal', true);
        }

        RateLimiter::hit($key, 3600);

        DB::table('user_registration_otps')->where(function ($query) use ($email, $mobile) {
            if ($email) {
                $query->where('email', $email);
            } else {
                $query->where('mobile', $mobile);
            }
        })->delete();

        DB::table('user_registration_otps')->insert([
            'email' => $email,
            'mobile' => $mobile,
            'email_otp_hash' => $email ? Hash::make($otp) : null,
            'mobile_otp_hash' => $mobile ? Hash::make($otp) : null,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request->session()->put('pending_user_registration', [
            'name' => $validated['name'],
            'email' => $email,
            'mobile' => $mobile,
            'channel' => $validated['channel'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('register.verify')
            ->with('status', 'OTP sent. Check your selected email address or mobile phone.');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('pending_user_registration')) {
            return redirect()->route('register')
                ->withErrors(['registration' => 'Start registration first.'], 'registration')
                ->with('show_register_modal', true);
        }

        return view('auth.user-verify');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'verification')
                ->with('show_verify_modal', true);
        }

        $pending = $request->session()->get('pending_user_registration');

        if (!$pending) {
            return redirect()->route('register')
                ->withErrors(['registration' => 'Your registration session expired. Start again.'], 'registration')
                ->with('show_register_modal', true);
        }

        $column = $pending['channel'] === 'email'
            ? 'email_otp_hash'
            : 'mobile_otp_hash';

        $record = DB::table('user_registration_otps')
            ->where($pending['channel'], $pending[$pending['channel']])
            ->first();

        if (!$record || Carbon::parse($record->expires_at)->isPast()) {
            return redirect()->route('register')
                ->withErrors(['otp' => 'OTP expired. Please start registration again.'], 'registration')
                ->with('show_register_modal', true);
        }

        if ($record->attempts >= 5) {
            return back()
                ->withErrors(['otp' => 'Maximum verification attempts reached. Start registration again.'], 'verification')
                ->with('show_verify_modal', true);
        }

        DB::table('user_registration_otps')
            ->where('id', $record->id)
            ->increment('attempts');

        if (!$record->$column || !Hash::check($request->otp, $record->$column)) {
            return back()
                ->withErrors(['otp' => 'Incorrect OTP. Please try again.'], 'verification')
                ->with('show_verify_modal', true);
        }

        $user = DB::transaction(function () use ($pending, $record) {
            $attributes = [
                'name' => $pending['name'],
                'email' => $pending['email'],
                'mobile' => $pending['mobile'],
                'password' => $pending['password'],
            ];

            if ($pending['channel'] === 'email') {
                $attributes['email_verified_at'] = now();
            } else {
                $attributes['mobile_verified_at'] = now();
            }

            $user = User::create($attributes);
            $user->is_admin = false;
            $user->save();

            DB::table('user_registration_otps')
                ->where('id', $record->id)
                ->delete();

            return $user;
        });

        $request->session()->forget('pending_user_registration');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')
            ->with('auth_login_success', 'Registration verified. Your account is ready.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('pending_user_registration');

        if (!$pending) {
            return redirect()->route('register')
                ->withErrors(['registration' => 'Start registration again.'], 'registration')
                ->with('show_register_modal', true);
        }

        $requestData = [
            'name' => $pending['name'],
            'channel' => $pending['channel'],
            'email' => $pending['email'],
            'mobile' => $pending['mobile'],
            'password' => 'TemporaryPassword123!',
            'password_confirmation' => 'TemporaryPassword123!',
        ];

        // Reuse the same delivery and cooldown controls without changing the pending password.
        $key = 'registration-otp:' . ($pending['email'] ?: $pending['mobile']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors(['otp' => 'Too many OTP requests. Please try later.'], 'verification')
                ->with('show_verify_modal', true);
        }

        $record = DB::table('user_registration_otps')
            ->where($pending['channel'], $pending[$pending['channel']])
            ->first();

        if ($record && $record->last_sent_at &&
            Carbon::parse($record->last_sent_at)->addSeconds(60)->isFuture()) {
            return back()
                ->withErrors(['otp' => 'Please wait 60 seconds before requesting another OTP.'], 'verification')
                ->with('show_verify_modal', true);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            if ($pending['channel'] === 'email') {
                if (config('mail.default') === 'log' ||
                    empty(config('mail.mailers.smtp.username')) ||
                    empty(config('mail.mailers.smtp.password'))) {
                    return back()
                        ->withErrors(['otp' => 'Email OTP delivery is not configured.'], 'verification')
                        ->with('show_verify_modal', true);
                }

                Mail::raw(
                    "Your VakilNeeti registration OTP is: {$otp}\n\nThis OTP expires in 10 minutes. Do not share it.",
                    fn ($message) => $message->to($pending['email'])
                        ->subject('VakilNeeti registration verification')
                );
            } else {
                $sid = config('vakilneeti.twilio_sid');
                $token = config('vakilneeti.twilio_token');
                $from = config('vakilneeti.twilio_from');

                if (!$sid || !$token || !$from) {
                    return back()
                        ->withErrors(['otp' => 'SMS OTP delivery is not configured.'], 'verification')
                        ->with('show_verify_modal', true);
                }

                $response = Http::asForm()->withBasicAuth($sid, $token)
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                        'From' => $from,
                        'To' => $pending['mobile'],
                        'Body' => "Your VakilNeeti registration OTP is {$otp}. It expires in 10 minutes."
                    ]);

                if (!$response->successful()) {
                    return back()
                        ->withErrors(['otp' => 'SMS provider could not send the OTP.'], 'verification')
                        ->with('show_verify_modal', true);
                }
            }
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withErrors(['otp' => 'OTP delivery failed. Check provider configuration.'], 'verification')
                ->with('show_verify_modal', true);
        }

        RateLimiter::hit($key, 3600);

        DB::table('user_registration_otps')->where('id', $record->id)->update([
            'email_otp_hash' => $pending['channel'] === 'email' ? Hash::make($otp) : null,
            'mobile_otp_hash' => $pending['channel'] === 'mobile' ? Hash::make($otp) : null,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'A new OTP has been sent.');
    }

    public function login(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'login')
                ->withInput($request->except('password'))
                ->with('show_login_modal', true);
        }

        $credentials = $validator->validated();
        $identifier = trim($credentials['identifier']);

        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        if ($field === 'mobile') {
            try {
                $identifier = $this->normalizeMobile($identifier);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['identifier' => $e->getMessage()], 'login')
                    ->withInput($request->only('identifier'))
                    ->with('show_login_modal', true);
            }
        } else {
            $identifier = Str::lower($identifier);
        }

        $user = User::where($field, $identifier)->first();

        if (!$user || ($field === 'email' && !$user->email_verified_at) ||
            ($field === 'mobile' && !$user->mobile_verified_at) ||
            !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors([
                    'identifier' => 'Invalid credentials or contact method not verified.'
                ], 'login')
                ->withInput($request->only('identifier'))
                ->with('show_login_modal', true);
        }

        Auth::login($user, $request->boolean('remember'));
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