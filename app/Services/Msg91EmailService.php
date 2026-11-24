<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends the sign-in / sign-up code by email through MSG91 (Email API, POST /api/v5/email/send).
 * VakilNiti makes the code and checks it itself (only a hash is stored); MSG91 only delivers it,
 * using the approved email template whose variable is "otp" (plus "name" and "minutes").
 */
class Msg91EmailService
{
    public const ENDPOINT = 'https://control.msg91.com/api/v5/email/send';

    public function isConfigured(): bool
    {
        return filled(config('services.msg91.key'))
            && filled(config('services.msg91.email_domain'))
            && filled(config('services.msg91.email_from'))
            && filled(config('services.msg91.otp_template_id'));
    }

    /**
     * Email the code. Returns null when it was sent, or a message to show the person when it could not be.
     */
    public function sendOtp(string $email, string $code, ?string $name = null, int $minutes = 10): ?string
    {
        if (! $this->isConfigured()) {
            // On a developer's own machine the code goes to the log, so sign-in can be tried without MSG91.
            if (app()->environment('local', 'testing')) {
                Log::info("Sign-in code for {$email}: {$code} (MSG91 email is not set up, so it was not emailed)");

                return null;
            }

            Log::warning('MSG91 email is not set up (MSG91_AUTH_KEY, MSG91_EMAIL_DOMAIN, MSG91_EMAIL_FROM, MSG91_EMAIL_OTP_TEMPLATE).');

            return 'Email sign-in is not available right now. Please try again later.';
        }

        $recipient = array_filter(['email' => $email, 'name' => $name]);

        try {
            // IPv4 only: MSG91's auth key allows listed IPs, and the server's IPv4 address is the one listed there.
            $response = Http::connectTimeout(5)->timeout(15)->acceptJson()->withOptions(['force_ip_resolve' => 'v4'])
                ->withHeaders(['authkey' => config('services.msg91.key')])
                ->post(self::ENDPOINT, [
                    'recipients' => [[
                        'to' => [$recipient],
                        // Template variables, written ##otp##, ##company_name##, ##name##, ##minutes## in MSG91.
                        'variables' => ['otp' => $code, 'company_name' => config('services.msg91.email_from_name') ?: 'VakilNiti', 'name' => $name ?: 'there', 'minutes' => (string) $minutes],
                    ]],
                    'from' => ['email' => config('services.msg91.email_from'), 'name' => config('services.msg91.email_from_name')],
                    'domain' => config('services.msg91.email_domain'),
                    'template_id' => config('services.msg91.otp_template_id'),
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('MSG91 email could not be reached', ['message' => $exception->getMessage()]);

            return 'We could not send the email just now. Please try again in a minute.';
        }

        if ($response->successful() && ! $response->json('hasError') && $response->json('status') !== 'fail') {
            return null;
        }

        Log::warning('MSG91 email was not accepted', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);

        return 'We could not send the email just now. Please try again in a minute.';
    }
}
