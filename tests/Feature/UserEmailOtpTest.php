<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Msg91EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserEmailOtpTest extends TestCase
{
    public function test_an_account_with_a_password_signs_in_without_a_code(): void
    {
        $user = User::factory()->create(['password_set_at' => now()]);
        $this->post(route('login.password'), ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('login_otp_email'));
    }

    use RefreshDatabase;

    private string $code = '';

    private bool $refuse = false;

    private string $smsCode = '';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.msg91.key' => 'test-auth-key',
            'services.msg91.email_domain' => 'mail.vakilniti.test',
            'services.msg91.email_from' => 'no-reply@mail.vakilniti.test',
            'services.msg91.email_from_name' => 'VakilNiti',
            'services.msg91.otp_template_id' => 'vakilniti_login_code',
        ]);

        // MSG91 accepts the email; the test reads the code from the request, as the person would from their inbox.
        Http::fake(function (HttpRequest $request) {
            // Phone log-in: AuthKey.io gets the 4-digit code in the query string.
            if (str_contains($request->url(), 'api.authkey.io')) {
                $this->smsCode = (string) ($request->data()['otp'] ?? '');

                return Http::response(['Message' => 'Submitted Successfully', 'LogID' => '1']);
            }

            if ($this->refuse) {
                return Http::response(['status' => 'fail', 'hasError' => true, 'errors' => ['domain' => ['Domain not verified']]], 422);
            }

            $this->code = (string) data_get($request->data(), 'recipients.0.variables.otp', '');

            return Http::response(['status' => 'success', 'hasError' => false, 'data' => ['unique_id' => 'test'], 'errors' => []]);
        });
    }

    public function test_sign_up_verifies_the_email_then_saves_the_advocate_profile(): void
    {
        $this->post(route('register.send-otp'), ['email' => 'Asha@Example.test'])
            ->assertRedirect(route('register.verify'));

        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->code);
        Http::assertSent(fn (HttpRequest $request) => $request->url() === Msg91EmailService::ENDPOINT
            && $request->hasHeader('authkey', 'test-auth-key')
            && data_get($request->data(), 'recipients.0.to.0.email') === 'asha@example.test'
            && data_get($request->data(), 'template_id') === 'vakilniti_login_code'
            && data_get($request->data(), 'recipients.0.variables.company_name') === 'VakilNiti'
            && data_get($request->data(), 'domain') === 'mail.vakilniti.test'
            && data_get($request->data(), 'from.email') === 'no-reply@mail.vakilniti.test');

        $this->post(route('register.verify.submit'), ['otp' => '000000'])->assertSessionHasErrors('otp', null, 'verification');
        $this->post(route('register.verify.submit'), ['otp' => $this->code])->assertRedirect(route('register.profile'));
        $this->assertGuest();

        $this->get(route('register.profile'))->assertOk()->assertSee('asha@example.test');

        $this->post(route('register.profile.submit'), [
            'account_role' => 'independent_practitioner',
            'password' => 'test-password-123',
            'password_confirmation' => 'test-password-123',
            'onboarding_features' => ['personalized_hearings', 'judgment_research'],
            'full_name' => 'Asha Advocate',
            'mobile' => '98765 43210',
            'bar_state' => 'Uttar Pradesh',
            'bar_state_code' => 'up',
            'bar_number' => '1234',
            'bar_registration_year' => 2017,
        ])->assertRedirect(route('cases.find'));

        $user = User::where('email', 'asha@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('+919876543210', $user->mobile);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('advocate_profiles', ['user_id' => $user->id, 'bar_council_registration_number' => 'UP/1234/2017']);
    }

    public function test_company_sign_up_works_without_a_mobile_number(): void
    {
        $this->post(route('register.send-otp'), ['email' => 'office@sharmalegal.example'])->assertRedirect(route('register.verify'));
        $this->post(route('register.verify.submit'), ['otp' => $this->code])->assertRedirect(route('register.profile'));

        $this->post(route('register.profile.submit'), [
            'account_role' => 'law_firm',
            'password' => 'test-password-123',
            'password_confirmation' => 'test-password-123',
            'onboarding_features' => ['invoices_expenses_documents'],
            'full_name' => 'Asha Sharma',
            'firm_name' => 'Sharma Legal Associates',
            'city' => 'Lucknow',
            'district' => 'Lucknow',
            'region' => 'Uttar Pradesh',
        ])->assertRedirect('/');

        $user = User::where('email', 'office@sharmalegal.example')->firstOrFail();
        $this->assertNull($user->mobile);
        $this->assertSame('company', $user->account_type);
        $this->assertDatabaseHas('firm_profiles', ['user_id' => $user->id, 'firm_name' => 'Sharma Legal Associates']);
    }

    public function test_an_email_already_registered_is_sent_to_log_in(): void
    {
        User::factory()->create(['email' => 'asha@example.test']);

        $this->post(route('register.send-otp'), ['email' => 'asha@example.test'])
            ->assertSessionHasErrors(['email' => 'This email is already registered. Please log in.'], null, 'registration');
        Http::assertNothingSent();
    }

    public function test_existing_users_log_in_with_the_emailed_code(): void
    {
        // Accounts made with a phone number before still have their email, and sign in with it.
        $user = User::create(['name' => 'Asha Advocate', 'email' => 'asha@example.test', 'mobile' => '+919876543210', 'password' => Hash::make('unused')]);

        $this->post(route('login.submit'), ['email' => ' ASHA@example.test '])
            ->assertRedirect(route('login'))
            ->assertSessionHas('show_login_otp', true);
        Http::assertSent(fn (HttpRequest $request) => data_get($request->data(), 'recipients.0.variables.name') === 'Asha Advocate');

        $this->post(route('login.verify.submit'), ['otp' => '12345'])->assertSessionHasErrors('otp', null, 'login');
        $this->assertGuest();

        $this->post(route('login.verify.submit'), ['otp' => $this->code])->assertRedirect(route('profile.index').'#password');
        $this->post(route('profile.password.store'), ['password' => 'first-password-42', 'password_confirmation' => 'first-password-42'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('first-password-42', $user->fresh()->password));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('user_registration_otps', ['email' => 'asha@example.test']);
    }

    public function test_an_unknown_email_and_a_refused_email_show_a_clear_message(): void
    {
        $this->post(route('login.submit'), ['email' => 'nobody@example.test'])
            ->assertSessionHasErrors(['email' => 'No account uses this email. Check it, or create an account.'], null, 'login');

        User::factory()->create(['email' => 'asha@example.test']);
        $this->refuse = true;

        $this->post(route('login.submit'), ['email' => 'asha@example.test'])
            ->assertSessionHasErrors(['email' => 'We could not send the email just now. Please try again in a minute.'], null, 'login');
        $this->assertDatabaseMissing('user_registration_otps', ['email' => 'asha@example.test']);
    }

    public function test_a_code_cannot_be_asked_for_again_within_a_minute_and_wrong_codes_run_out(): void
    {
        User::factory()->create(['email' => 'asha@example.test']);

        $this->post(route('login.submit'), ['email' => 'asha@example.test'])->assertSessionHasNoErrors();
        $this->post(route('login.submit'), ['email' => 'asha@example.test'])
            ->assertSessionHasErrors(['email' => 'Please wait 60 seconds before asking for another code.'], null, 'login');

        foreach (range(1, 5) as $try) {
            $this->post(route('login.verify.submit'), ['otp' => '000000']);
        }

        $this->post(route('login.verify.submit'), ['otp' => $this->code])
            ->assertSessionHasErrors(['otp' => 'Too many wrong tries. Ask for a new code.'], null, 'login');
        $this->assertGuest();
    }

    public function test_forgot_password_with_the_mobile_number_emails_the_code_without_showing_the_address(): void
    {
        // The number was given at sign-up and never confirmed by SMS: the emailed code is what proves the person.
        $user = User::create(['name' => 'Asha', 'email' => 'asha.sharma@example.test', 'mobile' => '+919876543210', 'password' => Hash::make('old-password'), 'password_set_at' => now()]);

        $this->post(route('login.submit'), ['login' => '98765 43210', 'password' => '', 'forgot' => '1'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_otp_mobile', '+919876543210')
            ->assertSessionHas('status', 'We have emailed a 6-digit code to as••••@example.test, the email on this mobile number.');
        $this->assertSame('', $this->smsCode);
        Http::assertSent(fn (HttpRequest $request) => data_get($request->data(), 'recipients.0.to.0.email') === 'asha.sharma@example.test');

        $this->get(route('login'))->assertOk()
            ->assertSee('as••••@example.test')->assertSee('Code from the email')->assertSee('pattern="[0-9]{6}"', false)
            ->assertDontSee('asha.sharma@example.test');

        $this->post(route('login.verify.submit'), ['otp' => $this->code])->assertRedirect(route('profile.index').'#password');
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->mobile_verified_at);
    }

    public function test_password_sign_in_takes_the_email_or_the_mobile_number(): void
    {
        $user = User::create(['name' => 'Asha', 'email' => 'asha@example.test', 'mobile' => '+919876543210', 'password' => Hash::make('court-diary-8'), 'password_set_at' => now()]);

        $this->post(route('login.password'), ['login' => '98765 43210', 'password' => 'wrong-one'])
            ->assertSessionHasErrors(['login' => 'That mobile number and password do not match.'], null, 'login');
        $this->assertGuest();

        $this->post(route('login.password'), ['login' => '+91 98765-43210', 'password' => 'court-diary-8'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $this->post(route('login.password'), ['login' => 'ASHA@example.test', 'password' => 'court-diary-8'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_log_in_box_has_password_and_email_code_and_an_unknown_mobile_stays_on_password(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('data-login-form="email"', false)->assertSee('data-login-form="code"', false)
            ->assertDontSee('data-login-form="mobile"', false)
            ->assertSee('Email or mobile number');

        $this->post(route('login.submit'), ['login' => '9876543210', 'forgot' => '1'])
            ->assertSessionHasErrors(['login' => 'No account uses this mobile number. Log in with your email instead.'], null, 'login')
            ->assertSessionHas('login_via', 'email');
        Http::assertNothingSent();

        $this->post(route('login.submit'), ['email' => 'nobody@example.test'])->assertSessionHas('login_via', 'code');
    }

    public function test_the_mobile_number_given_at_sign_up_logs_in_afterwards(): void
    {
        $this->post(route('register.send-otp'), ['email' => 'asha@example.test']);
        $this->post(route('register.verify.submit'), ['otp' => $this->code]);
        $this->post(route('register.profile.submit'), [
            'account_role' => 'independent_practitioner',
            'password' => 'court-diary-8',
            'password_confirmation' => 'court-diary-8',
            'onboarding_features' => ['personalized_hearings'],
            'full_name' => 'Asha Advocate',
            'mobile' => '98765 43210',
            'bar_state' => 'Uttar Pradesh',
            'bar_state_code' => 'up',
            'bar_number' => '1234',
            'bar_registration_year' => 2017,
        ])->assertRedirect(route('cases.find'));
        $user = User::where('email', 'asha@example.test')->firstOrFail();
        auth()->logout();

        // With the password…
        $this->post(route('login.password'), ['login' => '9876543210', 'password' => 'court-diary-8'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        auth()->logout();

        // …and "Forgot password?" with the number sends a code to the email the account was made with.
        $this->travel(2)->minutes();
        $this->post(route('login.submit'), ['login' => '98765 43210', 'forgot' => '1'])->assertSessionHasNoErrors();
        $this->post(route('login.verify.submit'), ['otp' => $this->code])->assertRedirect(route('profile.index').'#password');
        $this->assertAuthenticatedAs($user);
        $this->actingAs($user)->get(route('profile.index'))->assertOk()->assertDontSee('Mobile number for signing in');
    }

    public function test_changing_the_password_signs_out_every_other_device_without_any_setting(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => Hash::make('old-password'), 'password_set_at' => now(), 'remember_token' => 'old-remember-token']);
        $someoneElse = User::factory()->create();
        $session = fn (string $id, int $userId): array => ['id' => $id, 'user_id' => $userId, 'ip_address' => '10.0.0.1', 'user_agent' => 'Browser', 'payload' => '', 'last_activity' => now()->subMinutes(5)->timestamp];
        DB::table('sessions')->insert([$session('phone-session', $user->id), $session('office-session', $user->id), $session('other-person', $someoneElse->id)]);

        // No device list or extra button in Settings: it simply happens with a new password.
        $this->actingAs($user)->get(route('profile.index'))->assertOk()->assertDontSee('Sign out of all other devices');

        $this->post(route('profile.password.store'), ['current_password' => 'old-password', 'password' => 'new-password-8', 'password_confirmation' => 'new-password-8'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'phone-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'office-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-person']);
        $this->assertNotSame('old-remember-token', $user->fresh()->remember_token);
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_signs_in_with_a_code_and_lets_a_new_password_be_set(): void
    {
        $user = User::factory()->create(['email' => 'asha@example.test', 'password_set_at' => now()]);
        $this->get(route('courts.index'))->assertSee('Forgot password?')->assertSee('name="forgot" value="1"', false);

        $this->post(route('login.submit'), ['email' => 'asha@example.test', 'password' => '', 'forgot' => '1'])->assertSessionHasNoErrors();
        $this->post(route('login.verify.submit'), ['otp' => $this->code])->assertRedirect(route('profile.index').'#password');
        $this->assertAuthenticatedAs($user);

        $this->post(route('profile.password.store'), ['password' => 'fresh-start', 'password_confirmation' => 'fresh-start'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('fresh-start', $user->fresh()->password));
    }

    public function test_signing_in_with_a_code_or_password_keeps_the_device_signed_in(): void
    {
        User::factory()->create(['email' => 'asha@example.test']);
        $this->post(route('login.submit'), ['email' => 'asha@example.test']);
        $this->post(route('login.verify.submit'), ['otp' => $this->code])->assertCookie(auth()->guard()->getRecallerName());

        auth()->logout();
        $user = User::factory()->create(['password_set_at' => now()]);
        $this->post(route('login.password'), ['email' => $user->email, 'password' => 'password'])
            ->assertCookie(auth()->guard()->getRecallerName());
    }

    public function test_an_account_without_a_password_sets_one_without_a_current_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('profile.index'))->assertOk()
            ->assertSee('Set your sign-in password')->assertDontSee('name="current_password"', false);

        $this->post(route('profile.password.store'), ['password' => 'short12', 'password_confirmation' => 'short12'])
            ->assertSessionHasErrors('password', null, 'passwordSetup');
        $this->post(route('profile.password.store'), ['password' => 'mydiary8', 'password_confirmation' => 'mydiary8'])
            ->assertSessionHasNoErrors()->assertCookie(auth()->guard()->getRecallerName());

        $this->assertTrue(Hash::check('mydiary8', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->password_set_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_changing_an_existing_password_needs_the_current_one_and_keeps_this_device_signed_in(): void
    {
        $user = User::factory()->create(['password_set_at' => now()]);
        $this->actingAs($user)->get(route('profile.index'))->assertSee('name="current_password"', false);

        $this->post(route('profile.password.store'), ['password' => 'newpass88', 'password_confirmation' => 'newpass88'])
            ->assertSessionHasErrors('current_password', null, 'passwordSetup');
        $this->post(route('profile.password.store'), ['current_password' => 'wrong-one', 'password' => 'newpass88', 'password_confirmation' => 'newpass88'])
            ->assertSessionHasErrors('current_password', null, 'passwordSetup');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->post(route('profile.password.store'), ['current_password' => 'password', 'password' => 'newpass88', 'password_confirmation' => 'newpass88'])
            ->assertSessionHasNoErrors()->assertCookie(auth()->guard()->getRecallerName());
        $this->assertTrue(Hash::check('newpass88', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_test_command_reports_a_missing_set_up(): void
    {
        $this->artisan('app:test-login-email', ['email' => 'me@example.test'])->expectsOutputToContain('Sent.')->assertSuccessful();

        config(['services.msg91.key' => null]);
        $this->artisan('app:test-login-email', ['email' => 'me@example.test'])->expectsOutputToContain('MSG91 email is not set up')->assertFailed();
    }
}
