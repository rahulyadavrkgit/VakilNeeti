<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpBypassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // On a private machine in local mode with the bypass on, only listed emails may skip the emailed code.
        $this->app['env'] = 'local';
        config(['services.otp_bypass_enabled' => true, 'services.otp_bypass_emails' => ['asha@example.test']]);
        Http::fake();

        // In local mode the form token is checked even in tests, so each form sends the session's token.
        $this->withSession(['_token' => 'test-token']);
    }

    public function test_only_a_listed_email_signs_in_without_the_code(): void
    {
        $listed = User::factory()->create(['email' => 'asha@example.test']);
        User::factory()->create(['email' => 'ravi@example.test']);

        // Anyone else must receive and enter the emailed code.
        $this->post(route('login.submit'), ['email' => 'ravi@example.test', '_token' => 'test-token'])->assertRedirect()->assertSessionMissing('auth_login_success');
        $this->assertGuest();

        $this->post(route('login.submit'), ['email' => 'Asha@Example.test', '_token' => 'test-token'])->assertRedirect('/');
        $this->assertAuthenticatedAs($listed);
    }

    public function test_a_listed_mobile_number_still_logs_in_by_phone_as_before(): void
    {
        config(['services.otp_bypass_numbers' => ['98111 11111']]);
        $listed = User::factory()->create(['mobile' => '+919811111111']);

        $this->post(route('login.submit'), ['mobile' => '9811111111', '_token' => 'test-token'])->assertRedirect('/');
        $this->assertAuthenticatedAs($listed);
    }

    public function test_an_empty_list_lets_nobody_skip_the_code(): void
    {
        config(['services.otp_bypass_emails' => []]);
        User::factory()->create(['email' => 'asha@example.test']);

        $this->post(route('login.submit'), ['email' => 'asha@example.test', '_token' => 'test-token'])->assertRedirect()->assertSessionMissing('auth_login_success');
        $this->assertGuest();
    }

    public function test_the_bypass_never_works_outside_local_mode(): void
    {
        $this->app['env'] = 'production';
        User::factory()->create(['email' => 'asha@example.test']);

        $this->post(route('login.submit'), ['email' => 'asha@example.test', '_token' => 'test-token'])->assertRedirect()->assertSessionMissing('auth_login_success');
        $this->assertGuest();
    }
}
