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

        // The live site runs in local mode with the bypass on; only listed numbers may skip the SMS code.
        $this->app['env'] = 'local';
        config(['services.otp_bypass_enabled' => true, 'services.otp_bypass_numbers' => ['98111 11111']]);
        Http::fake(['verify.twilio.com/*' => Http::response(['status' => 'pending'], 201)]);

        // In local mode the form token is checked even in tests, so each form sends the session's token.
        $this->withSession(['_token' => 'test-token']);
    }

    public function test_only_a_listed_number_signs_in_without_the_sms_code(): void
    {
        $listed = User::factory()->create(['mobile' => '+919811111111']);
        User::factory()->create(['mobile' => '+919822222222']);

        // Anyone else must receive and enter the SMS code.
        $this->post(route('login.submit'), ['mobile' => '9822222222', '_token' => 'test-token'])->assertRedirect()->assertSessionMissing('auth_login_success');
        $this->assertGuest();

        $this->post(route('login.submit'), ['mobile' => '9811111111', '_token' => 'test-token'])->assertRedirect('/');
        $this->assertAuthenticatedAs($listed);
    }

    public function test_an_empty_list_lets_nobody_skip_the_code(): void
    {
        config(['services.otp_bypass_numbers' => []]);
        User::factory()->create(['mobile' => '+919811111111']);

        $this->post(route('login.submit'), ['mobile' => '9811111111', '_token' => 'test-token'])->assertRedirect()->assertSessionMissing('auth_login_success');
        $this->assertGuest();
    }
}
