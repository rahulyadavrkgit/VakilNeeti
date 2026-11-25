<?php

use App\Http\Controllers\CourtLinksController;
use App\Models\CourtBench;
use App\Models\User;
use App\Services\CourtBoardEmbedCheck;
use App\Services\ReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:send', function (ReminderService $reminders) {
    $sent = 0;

    User::query()->chunkById(100, function ($users) use ($reminders, &$sent) {
        foreach ($users as $user) {
            $sent += $reminders->sendFor($user);
        }
    });

    $this->info("Sent {$sent} reminders.");
})->purpose('Send bell reminders for hearings, appointments, tasks and overdue invoices');

Schedule::command('reminders:send')->hourly();

Artisan::command('courts:check-boards', function (CourtBoardEmbedCheck $check) {
    foreach (CourtLinksController::boardOptions() as $board) {
        $allowed = $check->refresh($board['url']);
        $this->line(($allowed ? 'embeddable  ' : 'not allowed ').$board['court'].' · '.$board['title']);
    }
})->purpose('Check which court display boards allow being shown inside VakilNiti');

Schedule::command('courts:check-boards')->dailyAt('06:00');

// After Hostinger's automatic deploy: migrate and rebuild the caches once for each new commit.
Schedule::command('app:refresh-after-deploy')->everyMinute()->withoutOverlapping();

// Delhi High Court cause lists: the next day's lists come out in the evening, supplementary lists in the morning.
Schedule::command('courts:delhi-hc-cause-list')->cron('15 7,9,20,22 * * *')->withoutOverlapping(60);

// Supreme Court: the next day's lists in the evening, supplementary lists in the morning.
Schedule::command('courts:cause-lists supreme-court')->cron('35 7,9,20,22 * * *')->withoutOverlapping(60);

// Allahabad High Court (one PDF per court, about a hundred courts): tomorrow's lists in the evening, today's again early.
Schedule::command('courts:cause-lists allahabad-high-court --when=tomorrow')->dailyAt('21:05')->withoutOverlapping(90);
Schedule::command('courts:cause-lists allahabad-high-court --when=today')->dailyAt('07:50')->withoutOverlapping(90);

// Every morning: a note in the bell if the site logged errors yesterday.
Schedule::command('app:error-alerts')->dailyAt('09:00');

// A compressed database copy every night, kept for 14 days (storage/app/backups).
Schedule::command('app:backup-database')->dailyAt('02:30')->withoutOverlapping();

Artisan::command('courts:import-benches {file : CSV with court_key, section, court_room, bench, location, judges, designations, roster, vc_link, vc_meeting_id, email, photos; lists separated by a vertical bar} {--replace : Replace the existing list of every court in the file}', function (string $file) {
    if (! is_readable($file)) {
        $this->error("Cannot read {$file}.");

        return 1;
    }

    $courts = CourtBench::courts();
    $handle = fopen($file, 'r');
    $header = array_map(fn ($column) => strtolower(trim((string) $column, " \t\n\r\0\x0B\xEF\xBB\xBF")), fgetcsv($handle) ?: []);
    $split = fn (?string $value) => array_values(array_filter(array_map('trim', explode('|', (string) $value)), fn ($part) => $part !== ''));
    $rows = [];
    $line = 1;

    while (($values = fgetcsv($handle)) !== false) {
        $line++;
        $row = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));

        if (! isset($courts[$row['court_key'] ?? ''])) {
            $this->warn("Line {$line}: unknown court_key '".($row['court_key'] ?? '')."', skipped. Known: ".implode(', ', array_keys($courts)));

            continue;
        }

        $vcLink = trim($row['vc_link'] ?? '');

        if ($vcLink !== '' && ! preg_match('#^https?://#i', $vcLink)) {
            $this->warn("Line {$line}: VC link is not a web address, left out.");
            $vcLink = '';
        }

        $designations = $split($row['designations'] ?? '');
        $photos = $split($row['photos'] ?? '');
        $judges = [];

        foreach ($split($row['judges'] ?? '') as $index => $name) {
            $judges[] = ['name' => $name, 'designation' => $designations[$index] ?? null, 'photo' => $photos[$index] ?? null];
        }

        $rows[] = [
            'court_key' => $row['court_key'],
            'section' => trim($row['section'] ?? '') ?: null,
            'court_room' => trim($row['court_room'] ?? '') ?: null,
            'bench' => trim($row['bench'] ?? '') ?: null,
            'location' => trim($row['location'] ?? '') ?: null,
            'judges' => $judges,
            'roster' => $split($row['roster'] ?? ''),
            'vc_link' => $vcLink ?: null,
            'vc_meeting_id' => trim($row['vc_meeting_id'] ?? '') ?: null,
            'email' => trim($row['email'] ?? '') ?: null,
        ];

        // Column sizes in court_benches: a longer value is cut (with a warning) instead of failing the whole import on MySQL.
        foreach (['section' => 60, 'court_room' => 60, 'bench' => 60, 'location' => 255, 'vc_link' => 500, 'vc_meeting_id' => 160, 'email' => 255] as $column => $maxLength) {
            $value = $rows[array_key_last($rows)][$column];

            if ($value !== null && mb_strlen($value) > $maxLength) {
                $this->warn("Line {$line}: {$column} is longer than {$maxLength} characters, shortened.");
                $rows[array_key_last($rows)][$column] = mb_substr($value, 0, $maxLength);
            }
        }
    }

    fclose($handle);

    DB::transaction(function () use ($rows) {
        if ($this->option('replace')) {
            CourtBench::query()->whereIn('court_key', array_unique(array_column($rows, 'court_key')))->delete();
        }

        foreach ($rows as $order => $row) {
            CourtBench::create($row + ['sort_order' => $order]);
        }
    });

    $this->info('Imported '.count($rows).' court rooms.');

    return 0;
})->purpose('Import court rooms, judges and VC links for the Supreme Court, Delhi High Court and Delhi district court lists');
