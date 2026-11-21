<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsService
{
    private const ERROR_MESSAGES = [
        '20003' => 'Twilio rejected authentication. Check your Twilio credentials.',
        '21211' => 'Twilio rejected the destination number. Check its country code and format.',
        '21408' => 'Twilio blocked this destination. Check your Twilio Geo Permissions.',
        '21608' => 'This Twilio trial account can send only to recipient numbers verified in Twilio.',
        '21610' => 'This recipient has opted out of messages.',
        '21614' => 'The destination is not a valid SMS-capable number.',
    ];

    /**
     * Start an SMS verification using Twilio Verify.
     * Returns null on success or a user-safe error message.
     */
    public function startVerification(string $to): ?string
    {
        $credentials = $this->verifyCredentials();

        if ($credentials['error'] !== null) {
            return $credentials['error'];
        }

        [$apiKey, $apiSecret, $serviceSid] = $credentials['values'];

        try {
            $response = Http::asForm()
                ->withBasicAuth($apiKey, $apiSecret)
                ->connectTimeout(5)
                ->timeout(15)
                ->post(
                    "https://verify.twilio.com/v2/Services/{$serviceSid}/Verifications",
                    [
                        'To' => $to,
                        'Channel' => 'sms',
                    ]
                );
        } catch (ConnectionException $exception) {
            report($exception);

            return 'Could not connect to Twilio. Check your internet connection and try again.';
        }

        if ($response->successful()) {
            return null;
        }

        return $this->twilioError($response, 'sending');
    }

    /**
     * Check an OTP using Twilio Verify.
     *
     * @return array{checked: bool, approved: bool, error: ?string}
     */
    public function checkVerification(string $to, string $code): array
    {
        $credentials = $this->verifyCredentials();

        if ($credentials['error'] !== null) {
            return [
                'checked' => false,
                'approved' => false,
                'error' => $credentials['error'],
            ];
        }

        [$apiKey, $apiSecret, $serviceSid] = $credentials['values'];

        try {
            $response = Http::asForm()
                ->withBasicAuth($apiKey, $apiSecret)
                ->connectTimeout(5)
                ->timeout(15)
                ->post(
                    "https://verify.twilio.com/v2/Services/{$serviceSid}/VerificationCheck",
                    [
                        'To' => $to,
                        'Code' => $code,
                    ]
                );
        } catch (ConnectionException $exception) {
            report($exception);

            return [
                'checked' => false,
                'approved' => false,
                'error' => 'Could not connect to Twilio to verify the OTP. Please try again.',
            ];
        }

        if (!$response->successful()) {
            return [
                'checked' => false,
                'approved' => false,
                'error' => $this->twilioError($response, 'verification'),
            ];
        }

        $approved = $response->json('status') === 'approved';

        return [
            'checked' => true,
            'approved' => $approved,
            'error' => null,
        ];
    }

    /**
     * Validate Verify credentials.
     *
     * @return array{error: ?string, values: array<int, string>}
     */
    private function verifyCredentials(): array
    {
        $accountSid = trim((string) config('services.twilio.account_sid'));
        $apiKey = trim((string) config('services.twilio.api_key'));
        $apiSecret = trim((string) config('services.twilio.api_secret'));
        $serviceSid = trim((string) config('services.twilio.verify_service_sid'));

        if (!preg_match('/^AC[a-f0-9]{32}$/i', $accountSid)) {
            return [
                'error' => 'Twilio Account SID is missing or invalid.',
                'values' => [],
            ];
        }

        if (!preg_match('/^SK[a-f0-9]{32}$/i', $apiKey) || $apiSecret === '') {
            return [
                'error' => 'Twilio API Key credentials are missing or invalid.',
                'values' => [],
            ];
        }

        if (!preg_match('/^VA[a-f0-9]{32}$/i', $serviceSid)) {
            return [
                'error' => 'Twilio Verify Service SID is missing or invalid.',
                'values' => [],
            ];
        }

        return [
            'error' => null,
            'values' => [$apiKey, $apiSecret, $serviceSid],
        ];
    }

    private function twilioError($response, string $operation): string
    {
        $errorCode = (string) $response->json('code', '');

        Log::warning('Twilio Verify request failed.', [
            'operation' => $operation,
            'http_status' => $response->status(),
            'error_code' => $errorCode !== '' ? $errorCode : null,
        ]);

        if (isset(self::ERROR_MESSAGES[$errorCode])) {
            return self::ERROR_MESSAGES[$errorCode];
        }

        return 'Twilio could not complete the OTP ' . $operation .
            ' request (HTTP ' . $response->status() .
            ($errorCode !== '' ? ', error ' . $errorCode : '') .
            '). Check Twilio Monitor logs.';
    }

    /**
     * Existing ordinary SMS method retained for compatibility.
     */
    public function send(string $to, string $body): ?string
    {
        $accountSid = trim((string) config('services.twilio.account_sid'));
        $authToken = trim((string) config('services.twilio.auth_token'));
        $from = trim((string) config('services.twilio.from'));
        $messagingServiceSid = trim((string) config('services.twilio.messaging_service_sid'));

        if (!preg_match('/^AC[a-f0-9]{32}$/i', $accountSid) || $authToken === '') {
            return 'Twilio is not configured correctly. Check the account SID and auth token.';
        }

        if ($messagingServiceSid !== '') {
            if (!preg_match('/^MG[a-f0-9]{32}$/i', $messagingServiceSid)) {
                return 'The Twilio Messaging Service SID must start with MG and contain 32 hexadecimal characters.';
            }

            $payload = [
                'MessagingServiceSid' => $messagingServiceSid,
                'To' => $to,
                'Body' => $body,
            ];
        } else {
            if (!preg_match('/^\+[1-9]\d{7,14}$/', $from)) {
                return 'Set a valid E.164 sender number in TWILIO_FROM or configure TWILIO_MESSAGING_SERVICE_SID.';
            }

            $payload = [
                'From' => $from,
                'To' => $to,
                'Body' => $body,
            ];
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($accountSid, $authToken)
                ->connectTimeout(3)
                ->timeout(10)
                ->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json",
                    $payload
                );
        } catch (ConnectionException $exception) {
            report($exception);

            return 'Could not connect to Twilio. Check server internet access and try again.';
        }

        if ($response->successful()) {
            return null;
        }

        $errorCode = (string) $response->json('code', '');

        Log::warning('Twilio rejected SMS delivery.', [
            'http_status' => $response->status(),
            'error_code' => $errorCode !== '' ? $errorCode : null,
        ]);

        if (isset(self::ERROR_MESSAGES[$errorCode])) {
            return self::ERROR_MESSAGES[$errorCode];
        }

        return 'Twilio rejected the SMS (HTTP ' . $response->status() .
            ($errorCode !== '' ? ', error ' . $errorCode : '') .
            '). Check Twilio Monitor > Logs > Messaging.';
    }
}
