<?php

namespace Tests\Feature\Services;

use App\Services\Msg91EmailService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class Msg91EmailServiceTest extends TestCase
{
    public function test_without_a_set_up_the_live_site_says_so_and_a_developer_gets_the_code_in_the_log(): void
    {
        config(['services.msg91.key' => null]);
        Http::fake();

        $this->app['env'] = 'production';
        $this->assertSame('Email sign-in is not available right now. Please try again later.', app(Msg91EmailService::class)->sendOtp('asha@example.test', '482193'));

        $this->app['env'] = 'local';
        Log::spy();
        $this->assertNull(app(Msg91EmailService::class)->sendOtp('asha@example.test', '482193'));
        Log::shouldHaveReceived('info')->withArgs(fn (string $message) => str_contains($message, 'asha@example.test: 482193'));

        Http::assertNothingSent();
    }

    public function test_an_unreachable_msg91_gives_a_friendly_message(): void
    {
        config([
            'services.msg91.key' => 'key', 'services.msg91.email_domain' => 'mail.example.test',
            'services.msg91.email_from' => 'no-reply@mail.example.test', 'services.msg91.otp_template_id' => 'login_code',
        ]);
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->assertSame('We could not send the email just now. Please try again in a minute.', app(Msg91EmailService::class)->sendOtp('asha@example.test', '482193'));
    }
}
