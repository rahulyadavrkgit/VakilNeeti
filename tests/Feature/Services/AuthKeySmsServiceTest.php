<?php

namespace Tests\Feature\Services;

use App\Contracts\OtpSmsSender;
use App\Services\AuthKeySmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthKeySmsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.authkey.key' => 'test-auth-key', 'services.authkey.otp_sid' => '1234']);
    }

    public function test_sends_the_code_through_the_approved_template(): void
    {
        Http::fake(['api.authkey.io/*' => Http::response(['LogID' => 'abc123', 'Message' => 'Submitted Successfully'])]);

        $this->assertNull(app(AuthKeySmsService::class)->sendOtp('+919876543210', '4821'));

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.authkey.io/request')
            && $request['authkey'] === 'test-auth-key'
            && $request['mobile'] === '9876543210'
            && $request['country_code'] === '91'
            && $request['sid'] === '1234'
            && $request['otp'] === '4821');
    }

    public function test_a_refused_message_or_missing_setup_gives_a_clear_error(): void
    {
        Http::fake(['api.authkey.io/*' => Http::response(['Message' => 'Insufficient balance'])]);
        $this->assertSame('The code could not be sent. Please check the number and try again.', app(AuthKeySmsService::class)->sendOtp('+919876543210', '4821'));

        config(['services.authkey.otp_sid' => '']);
        $this->assertSame('SMS sign-in is not set up yet. Please try again later.', app(AuthKeySmsService::class)->sendOtp('+919876543210', '4821'));
    }

    public function test_phone_codes_are_sent_through_authkey(): void
    {
        $this->assertInstanceOf(AuthKeySmsService::class, app(OtpSmsSender::class));
    }
}
