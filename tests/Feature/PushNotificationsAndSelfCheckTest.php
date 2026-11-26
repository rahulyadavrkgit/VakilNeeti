<?php

namespace Tests\Feature;

use App\Models\CourtDocument;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\CauseLists\CauseListRecorder;
use App\Services\WebPush;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushNotificationsAndSelfCheckTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{public: string, private: string, pem: string} */
    private array $keys;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keys = $this->vapidKeys();
        config([
            'services.webpush.public_key' => $this->keys['public'],
            'services.webpush.private_key' => $this->keys['private'],
            'services.webpush.subject' => 'mailto:support@vakilniti.test',
        ]);
    }

    public function test_a_browser_turns_on_pushes_and_every_alert_reaches_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('notifications.index'))->assertOk()
            ->assertSee('name="vn-push-key" content="'.$this->keys['public'].'"', false)
            ->assertSee('data-push-enable', false)->assertSee('Add to Home Screen');

        $this->postJson(route('push-subscriptions.store'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'p', 'auth' => 'a']])->assertOk();
        $this->postJson(route('push-subscriptions.store'), ['endpoint' => 'http://insecure.example/x'])->assertUnprocessable();
        $this->assertSame(1, $user->pushSubscriptions()->count());

        Http::fake(['fcm.googleapis.com/*' => Http::response('', 201)]);
        $user->notify(new ReminderNotification('hearing', 'Listed tomorrow · Delhi HC · Court 1 · Item 3', 'W.P.(C) 298/2008', route('cases.index'), 'k1'));

        // The push carries no text, only a signed "there is a new alert" for that push service.
        Http::assertSent(function (HttpRequest $request): bool {
            $authorization = $request->header('Authorization')[0] ?? '';

            return $request->url() === 'https://fcm.googleapis.com/fcm/send/abc' && $request->body() === ''
                && str_starts_with($authorization, 'vapid t=') && str_ends_with($authorization, ', k='.$this->keys['public']);
        });
        $this->assertNotNull($user->pushSubscriptions()->first()->last_pushed_at);

        // The phone then fetches the alert's text, and tapping it opens the case list (a full address on this site).
        $latest = $this->getJson(route('notifications.latest'))->assertOk()->json();
        $this->assertSame('Listed tomorrow · Delhi HC · Court 1 · Item 3', $latest['title']);
        $this->get($latest['url'])->assertRedirect('/cases');
    }

    public function test_the_push_token_is_a_valid_es256_signature_and_gone_browsers_are_removed(): void
    {
        $token = app(WebPush::class)->token('https://web.push.apple.com');
        [$header, $claims, $signature] = explode('.', $token);
        $decode = fn (string $part): string => (string) base64_decode(strtr($part, '-_', '+/'));

        $this->assertSame(['typ' => 'JWT', 'alg' => 'ES256'], json_decode($decode($header), true));
        $this->assertSame('https://web.push.apple.com', json_decode($decode($claims), true)['aud']);
        $this->assertSame(1, openssl_verify($header.'.'.$claims, $this->derSignature($decode($signature)), $this->keys['pem'], OPENSSL_ALGO_SHA256));

        $user = User::factory()->create();
        PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://web.push.apple.com/gone']);
        Http::fake(['web.push.apple.com/*' => Http::response('', 410)]);
        $user->notify(new ReminderNotification('task', 'Task due', 'File reply', '/tasks', 'k2'));

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_signing_out_removes_this_browser_from_pushes(): void
    {
        $user = User::factory()->create();
        PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://fcm.googleapis.com/fcm/send/mine']);

        $this->actingAs($user)->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/mine'])->assertNoContent();
        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_the_nightly_check_is_quiet_when_all_is_well_and_tells_the_admin_otherwise(): void
    {
        Http::fake();
        $this->travelTo('2026-11-26 23:00:00');
        config(['services.msg91' => ['key' => 'k', 'email_domain' => 'd', 'email_from' => 'f', 'otp_template_id' => 't']]);
        $admin = User::factory()->create(['is_admin' => true]);
        $advocate = User::factory()->create();
        $this->actingAs($advocate);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'next_hearing_date' => '2026-11-27']);
        app(CauseListRecorder::class)->ensureSource('delhi-high-court', 'High Court of Delhi', 'https://delhihighcourt.nic.in');
        CourtDocument::create(['provider_key' => 'delhi-high-court', 'document_key' => 'today', 'source_url' => 'https://delhihighcourt.nic.in/files/c.pdf', 'list_type' => 'cause_list', 'extraction_status' => 'done', 'fetched_at' => now()->subHours(3)]);
        auth()->logout();

        $this->artisan('app:self-check')->expectsOutputToContain('All checks passed.')->assertSuccessful();
        $this->assertSame(0, $admin->notifications()->count());

        // The court's list stopped being read four days ago, and the email settings went missing.
        CourtDocument::query()->update(['fetched_at' => now()->subDays(4)]);
        config(['services.msg91.key' => null]);

        $this->artisan('app:self-check')->assertSuccessful();
        $this->artisan('app:self-check')->assertSuccessful();

        $alert = $admin->notifications()->sole()->data;
        $this->assertSame('Nightly check: 2 things need a look', $alert['title']);
        $this->assertStringContainsString('No Delhi High Court cause list read for 3 days', $alert['body']);
        $this->assertStringContainsString('MSG91 settings are incomplete', $alert['body']);
        $this->assertSame(0, $advocate->notifications()->count());

        $expression = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'app:self-check'))?->expression;
        $this->assertSame('0 23 * * *', $expression);
    }

    /**
     * A fresh P-256 key pair in the VAPID form, plus its public key as PEM for checking signatures.
     *
     * @return array{public: string, private: string, pem: string}
     */
    private function vapidKeys(): array
    {
        $options = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $key = openssl_pkey_new($options) ?: openssl_pkey_new($options + ['config' => getenv('OPENSSL_CONF') ?: 'C:/php/extras/ssl/openssl.cnf']);
        $details = openssl_pkey_get_details($key);
        $encode = fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $pad = fn (string $value): string => str_pad($value, 32, "\x00", STR_PAD_LEFT);

        return [
            'public' => $encode("\x04".$pad($details['ec']['x']).$pad($details['ec']['y'])),
            'private' => $encode($pad($details['ec']['d'])),
            'pem' => $details['key'],
        ];
    }

    private function derSignature(string $raw): string
    {
        $integer = function (string $bytes): string {
            $bytes = ltrim($bytes, "\x00");
            $bytes = (ord($bytes[0]) & 0x80) ? "\x00".$bytes : $bytes;

            return "\x02".chr(strlen($bytes)).$bytes;
        };
        $body = $integer(substr($raw, 0, 32)).$integer(substr($raw, 32, 32));

        return "\x30".chr(strlen($body)).$body;
    }
}
