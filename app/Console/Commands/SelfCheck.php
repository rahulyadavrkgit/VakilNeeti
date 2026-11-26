<?php

namespace App\Console\Commands;

use App\Models\CourtDocument;
use App\Models\CourtHearing;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\CalendarEventService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Every night, after the evening cause lists: check that the work done for advocates in the background is still
 * working, and tell the site's caretakers (Alerts, and a push to their phones) only when something is not.
 * A quiet night sends nothing.
 */
#[Signature('app:self-check')]
#[Description('Check the cause lists, backups, tomorrow\'s hearings, email and disk; alert the caretakers on problems')]
class SelfCheck extends Command
{
    /**
     * Cause-list readers and their names. A reader in use that has read no list for this many days is reported;
     * three days leaves room for a weekend or a court holiday.
     *
     * @var array<string, string>
     */
    private const COURT_LISTS = [
        'delhi-high-court' => 'Delhi High Court',
        'supreme-court' => 'Supreme Court',
        'allahabad-high-court' => 'Allahabad High Court',
    ];

    private const QUIET_DAYS = 3;

    public function handle(CalendarEventService $calendar): int
    {
        $problems = [
            ...$this->courtListsNotRead(),
            ...$this->missingBackup(),
            ...$this->hearingsMissingTomorrow($calendar),
            ...$this->emailNotSetUp(),
            ...$this->lowDiskSpace(),
        ];

        if ($problems === []) {
            $this->info('All checks passed.');

            return self::SUCCESS;
        }

        $key = 'self-check:'.now()->toDateString();

        foreach (User::siteCaretakers() as $user) {
            if (! $user->notifications()->where('data->key', $key)->exists()) {
                $user->notify(new ReminderNotification(
                    kind: 'alert',
                    title: 'Nightly check: '.count($problems).' '.(count($problems) === 1 ? 'thing needs' : 'things need').' a look',
                    body: mb_substr(implode(' · ', $problems), 0, 480),
                    url: '/notifications',
                    key: $key,
                ));
            }
        }

        foreach ($problems as $problem) {
            $this->warn($problem);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function courtListsNotRead(): array
    {
        $problems = [];

        foreach (self::COURT_LISTS as $provider => $court) {
            $inUse = CourtDocument::query()->where('provider_key', $provider)->where('fetched_at', '>=', now()->subDays(30))->exists();
            $readRecently = CourtDocument::query()->where('provider_key', $provider)->where('extraction_status', 'done')
                ->where('fetched_at', '>=', now()->subDays(self::QUIET_DAYS))->exists();

            if ($inUse && ! $readRecently) {
                $problems[] = "No {$court} cause list read for ".self::QUIET_DAYS.' days (a court holiday, or the court changed its website)';
            }
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    private function missingBackup(): array
    {
        if (config('database.connections.'.config('database.default').'.driver') !== 'mysql') {
            return [];
        }

        $newest = collect(glob(storage_path('app/backups/db-*.sql.gz')) ?: [])->map(fn (string $file): int => (int) filemtime($file))->max();

        return $newest && $newest >= now()->subHours(26)->timestamp ? [] : ['No database backup in the last day'];
    }

    /**
     * Every hearing saved for tomorrow must be in that advocate's diary for tomorrow (the calendar, Home and reminders).
     *
     * @return list<string>
     */
    private function hearingsMissingTomorrow(CalendarEventService $calendar): array
    {
        $tomorrow = today()->addDay()->toDateString();
        $saved = CourtHearing::query()->withoutGlobalScopes()
            ->join('legal_cases', 'legal_cases.id', '=', 'court_hearings.case_id')
            ->whereNull('legal_cases.deleted_at')
            ->where(fn ($dates) => $dates->whereDate('court_hearings.hearing_date', $tomorrow)->orWhereDate('court_hearings.next_hearing_date', $tomorrow))
            ->get(['court_hearings.case_id', 'legal_cases.user_id'])
            ->groupBy('user_id');

        $missing = 0;

        foreach ($saved as $userId => $hearings) {
            $shown = $calendar->between(today()->addDay(), today()->addDay(), ['groups' => ['hearing'], 'user_id' => (int) $userId])
                ->pluck('case_id')->filter()->unique();
            $missing += $hearings->pluck('case_id')->unique()->diff($shown)->count();
        }

        return $missing ? ["{$missing} ".($missing === 1 ? 'hearing' : 'hearings').' saved for tomorrow do not show in the diary'] : [];
    }

    /**
     * @return list<string>
     */
    private function emailNotSetUp(): array
    {
        $needed = ['key', 'email_domain', 'email_from', 'otp_template_id'];
        $missing = array_filter($needed, fn (string $setting): bool => blank(config('services.msg91.'.$setting)));

        return $missing ? ['Email sign-in codes cannot be sent: the MSG91 settings are incomplete'] : [];
    }

    /**
     * @return list<string>
     */
    private function lowDiskSpace(): array
    {
        $free = @disk_free_space(storage_path());

        return $free !== false && $free < 500 * 1024 * 1024 ? ['Less than 500 MB of disk space left ('.round($free / 1048576).' MB)'] : [];
    }
}
