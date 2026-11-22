<?php

use App\Http\Controllers\CourtLinksController;
use App\Models\User;
use App\Services\CourtBoardEmbedCheck;
use App\Services\ReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
