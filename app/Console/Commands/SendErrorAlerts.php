<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\RecentSiteErrors;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
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
    public function handle(RecentSiteErrors $siteErrors): int
    {
        $since = now()->subHours((int) $this->option('hours'));
        $errors = $siteErrors->since($since);

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
