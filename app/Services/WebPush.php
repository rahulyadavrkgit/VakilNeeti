<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Push notifications without a payload: VakilNiti tells the browser's push service "there is a new alert", signed
 * with the site's VAPID key, and the phone's service worker (public/sw.js) fetches the alert's text from VakilNiti
 * itself. No alert text passes through Google's or Apple's push servers, and nothing needs encrypting.
 *
 * Keys (VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY) are made once with `php artisan webpush:keys`.
 */
class WebPush
{
    public function isConfigured(): bool
    {
        return filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key'));
    }

    /**
     * Send one "new alert" signal. Returns false when the browser has unsubscribed (the row is then removed).
     */
    public function ping(PushSubscription $subscription): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $parts = parse_url($subscription->endpoint);
            $audience = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

            $response = Http::timeout(8)->connectTimeout(4)
                ->withHeaders([
                    'TTL' => '86400',
                    'Urgency' => 'high',
                    'Authorization' => 'vapid t='.$this->token($audience).', k='.config('services.webpush.public_key'),
                ])
                ->withBody('', 'application/octet-stream')
                ->post($subscription->endpoint);
        } catch (Throwable $exception) {
            Log::warning('Push notification could not be sent', ['message' => $exception->getMessage()]);

            return true;
        }

        // 404 or 410: the browser no longer wants pushes (unsubscribed, or the app was removed).
        if (in_array($response->status(), [404, 410], true)) {
            $subscription->delete();

            return false;
        }

        if ($response->successful()) {
            $subscription->forceFill(['last_pushed_at' => now()])->save();
        } else {
            Log::warning('Push service refused a notification', ['status' => $response->status(), 'host' => $parts['host'] ?? null]);
        }

        return true;
    }

    /**
     * A VAPID JSON Web Token (ES256) for one push service, valid for 12 hours.
     */
    public function token(string $audience): string
    {
        $encode = fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $unsigned = $encode((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']))
            .'.'.$encode((string) json_encode(['aud' => $audience, 'exp' => now()->addHours(12)->timestamp, 'sub' => (string) config('services.webpush.subject')]));

        if (! openssl_sign($unsigned, $derSignature, $this->privateKeyPem(), OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('The VAPID key could not sign the push request.');
        }

        return $unsigned.'.'.$encode(self::rawSignature($derSignature));
    }

    /**
     * The raw keys (base64url, the standard VAPID form) as a PEM EC private key that OpenSSL can sign with.
     */
    private function privateKeyPem(): string
    {
        $decode = fn (string $data): string => (string) base64_decode(strtr($data, '-_', '+/'), true);
        $private = $decode((string) config('services.webpush.private_key'));
        $public = $decode((string) config('services.webpush.public_key'));

        // ECPrivateKey (RFC 5915) on prime256v1: version 1, the 32-byte key, the curve, and the 65-byte public point.
        $der = hex2bin('30770201010420').$private.hex2bin('a00a06082a8648ce3d030107a144034200').$public;

        return "-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END EC PRIVATE KEY-----\n";
    }

    /**
     * OpenSSL signs in DER (two integers); a JWT needs them as 32 + 32 raw bytes.
     */
    public static function rawSignature(string $der): string
    {
        $offset = 2;
        $parts = [];

        foreach ([0, 1] as $index) {
            $length = ord($der[$offset + 1]);
            $integer = ltrim(substr($der, $offset + 2, $length), "\x00");
            $parts[] = str_pad($integer, 32, "\x00", STR_PAD_LEFT);
            $offset += 2 + $length;
        }

        return $parts[0].$parts[1];
    }
}
