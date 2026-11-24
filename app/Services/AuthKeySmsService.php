<?php

namespace App\Services;

use App\Contracts\OtpSmsSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends sign-in codes through AuthKey.io (https://authkey.io/sms-api-docs) using a DLT-approved
 * template saved in the AuthKey console. The template holds the code as {#otp#}, for example:
 * "{#otp#} is your VakilNiti login code. It is valid for 10 minutes. Do not share it."
 * AUTHKEY_API_KEY is the account's auth key; AUTHKEY_OTP_SID is that template's SID.
 */
class AuthKeySmsService implements OtpSmsSender
{
    private const ENDPOINT = 'https://api.authkey.io/request';

    public function sendOtp(string $mobile, string $code): ?string
    {
        $key = trim((string) config('services.authkey.key'));
        $sid = trim((string) config('services.authkey.otp_sid'));

        if ($key === '' || $sid === '') {
            return 'SMS sign-in is not set up yet. Please try again later.';
        }

        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        $countryCode = (string) config('services.authkey.country_code', '91');
        $number = str_starts_with($digits, $countryCode) && strlen($digits) > 10 ? substr($digits, strlen($countryCode)) : $digits;

        try {
            $response = Http::connectTimeout(5)->timeout(15)->acceptJson()->get(self::ENDPOINT, [
                'authkey' => $key,
                'mobile' => $number,
                'country_code' => $countryCode,
                'sid' => $sid,
                'otp' => $code,
            ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return 'Could not reach the SMS service. Please try again in a minute.';
        }

        $message = (string) ($response->json('Message') ?? $response->json('message') ?? '');
        $sent = $response->successful() && ($response->json('LogID') || stripos($message, 'submitted') !== false);

        if (! $sent) {
            // The key never appears in the log; only AuthKey's reply does.
            Log::warning('AuthKey OTP SMS was not accepted', ['status' => $response->status(), 'reply' => mb_substr($response->body(), 0, 300)]);

            return 'The code could not be sent. Please check the number and try again.';
        }

        return null;
    }
}
