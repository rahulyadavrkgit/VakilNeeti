<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Every morning: if the site logged errors in the last 24 hours, put a note in the bell of the people
 * who look after the site (admins, and the mobile numbers in ERROR_ALERT_MOBILES), with how many and
 * the most frequent messages, so problems are noticed the same day instead of by chance.
 */
#[Signature('app:error-alerts {--hours=24 : How far back to look}')]
#[Description('Tell the site\'s caretakers in the bell when errors were logged')]
class SendErrorAlerts extends Command
{
    public function handle(): int
    {
        $since = now()->subHours((int) $this->option('hours'));
        $errors = $this->errorsSince($since);

        if ($errors === []) {
            $this->info('No errors logged since '.$since->format('d M H:i').'.');

            return self::SUCCESS;
        }

        $top = collect($errors)->countBy()->sortDesc()->take(3);
        $body = $top->map(fn (int $count, string $message): string => $count.'× '.$message)->implode(' · ');
        $recipients = $this->recipients();
        $key = 'errors:'.now()->toDateString();

        foreach ($recipients as $user) {
            if ($user->notifications()->where('data->key', $key)->exists()) {
                continue;
            }

            $user->notify(new ReminderNotification(
                kind: 'alert',
                title: count($errors).' site '.(count($errors) === 1 ? 'error' : 'errors').' in the last '.$this->option('hours').' hours',
                body: mb_substr($body, 0, 480),
                url: '/notifications',
                key: $key,
            ));
        }

        $this->info(count($errors).' errors; told '.$recipients->count().' '.($recipients->count() === 1 ? 'person' : 'people').'.');

        return self::SUCCESS;
    }

    /**
     * The message of each error line logged after the given moment (stack traces are skipped).
     *
     * @return list<string>
     */
    private function errorsSince(Carbon $since): array
    {
        $file = storage_path('logs/laravel.log');

        if (! is_readable($file)) {
            return [];
        }

        // Read only the last 2 MB: a day of errors fits easily, and a very large log stays fast.
        $handle = fopen($file, 'r');
        $size = filesize($file);
        fseek($handle, max(0, $size - 2_000_000));
        $text = stream_get_contents($handle);
        fclose($handle);

        preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(?:ERROR|CRITICAL|ALERT|EMERGENCY): (.{1,160})/m', (string) $text, $matches, PREG_SET_ORDER);

        $messages = [];

        foreach ($matches as [, $at, $message]) {
            if (Carbon::parse($at, config('app.timezone'))->gte($since)) {
                // Strip view paths and ids so the same error groups together.
                $messages[] = trim((string) preg_replace(['/\(View: [^)]*\)/', '/\s+\{.*$/', '/\d{3,}/'], ['', '', '#'], $message));
            }
        }

        return $messages;
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients()
    {
        $lastTen = fn (string $number): string => substr(preg_replace('/\D+/', '', $number) ?? '', -10);
        $mobiles = collect(config('services.alerts.mobiles', []))->map($lastTen)->filter()->all();

        return User::query()->get()->filter(fn (User $user): bool => $user->is_admin
            || ($user->mobile && in_array($lastTen($user->mobile), $mobiles, true)))->values();
    }
}
