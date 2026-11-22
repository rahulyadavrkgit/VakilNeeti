<?php

use App\Models\User;
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
