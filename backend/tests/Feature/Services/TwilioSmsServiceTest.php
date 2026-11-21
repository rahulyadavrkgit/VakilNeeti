<?php

namespace Tests\Feature\Services;

use App\Services\TwilioSmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwilioSmsServiceTest extends TestCase
{
    private const ACCOUNT_SID = 'AC00000000000000000000000000000000';

    public function test_it_sends_sms_from_the_configured_phone_number(): void
    {
        config([
            'services.twilio.account_sid' => self::ACCOUNT_SID,
            'services.twilio.auth_token' => 'test-token',
            'services.twilio.from' => '+15005550006',
            'services.twilio.messaging_service_sid' => null,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SMtest'], 201),
        ]);

        $error = app(TwilioSmsService::class)->send('+919876543210', 'Test message');

        $this->assertNull($error);
        Http::assertSent(fn (Request $request): bool =>
            $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/'.self::ACCOUNT_SID.'/Messages.json'
            && str_contains($request->body(), 'From=%2B15005550006')
            && str_contains($request->body(), 'To=%2B919876543210')
        );
    }

    public function test_it_uses_the_messaging_service_and_reports_twilio_errors(): void
    {
        config([
            'services.twilio.account_sid' => self::ACCOUNT_SID,
            'services.twilio.auth_token' => 'test-token',
            'services.twilio.from' => null,
            'services.twilio.messaging_service_sid' => 'MG00000000000000000000000000000000',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'api.twilio.com/*' => Http::response([
                'code' => 21608,
                'message' => 'The number is not verified.',
            ], 400),
        ]);

        $error = app(TwilioSmsService::class)->send('+919876543210', 'Test message');

        $this->assertSame(
            'This Twilio trial account can send only to recipient numbers verified in Twilio.',
            $error
        );
        Http::assertSent(fn (Request $request): bool =>
            str_contains($request->body(), 'MessagingServiceSid=MG00000000000000000000000000000000')
            && !str_contains($request->body(), 'From=')
        );
    }
}
