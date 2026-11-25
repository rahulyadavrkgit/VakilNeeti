<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Creates the bell reminders: hearings and appointments today or tomorrow,
 * tasks due today or overdue, and overdue invoices. Each reminder is sent once.
 */
class ReminderService
{
    /**
     * From this hour, tomorrow's hearings are summed up in one evening reminder.
     */
    private const EVENING_HOUR = 17;

    public function __construct(private CalendarEventService $calendar) {}

    /**
     * Send reminders for a user at most every 30 minutes (used when pages load, so reminders work without cron).
     */
    public function sendIfDue(User $user): void
    {
        if (Cache::add("reminders-checked:{$user->id}", true, now()->addMinutes(30))) {
            $this->sendFor($user);
        }
    }

    /**
     * Send any reminders the user has not had yet. Returns how many were sent.
     */
    public function sendFor(User $user): int
    {
        $sentKeys = $user->notifications()
            ->where('type', ReminderNotification::class)
            ->where('created_at', '>=', now()->subDays(90))
            ->get(['data'])
            ->pluck('data.key')
            ->flip();

        $sent = 0;

        foreach ($this->dueReminders($user) as $reminder) {
            if ($sentKeys->has($reminder->key)) {
                continue;
            }

            $user->notify($reminder);
            $sentKeys->put($reminder->key, true);
            $sent++;
        }

        return $sent;
    }

    /**
     * @return Collection<int, ReminderNotification>
     */
    private function dueReminders(User $user): Collection
    {
        $today = today();
        $reminders = collect();

        // System Preferences: which reminders the person wants, and how early for hearings.
        $hearingReminders = $user->preference('remind_hearings');
        $groups = array_values(array_filter([
            $hearingReminders !== 'off' ? 'hearing' : null,
            $user->preference('remind_appointments') ? 'appointment' : null,
        ]));

        $events = $groups === [] ? collect() : $this->calendar->between($today, $today->copy()->addDay(), [
            'groups' => $groups,
            'user_id' => $user->workspaceId(),
        ]);

        $tomorrowHearings = [];

        foreach ($events as $event) {
            if ($event['group'] === 'hearing' && $event['date'] !== $today->toDateString()) {
                // Tomorrow's hearings come as one evening summary instead of one note each.
                if ($hearingReminders === 'today_and_tomorrow') {
                    $tomorrowHearings[] = $event;
                }

                continue;
            }

            $when = $event['date'] === $today->toDateString() ? 'today' : 'tomorrow';
            $kind = $event['group'];

            $reminders->push(new ReminderNotification(
                kind: $kind,
                title: ($kind === 'hearing' ? 'Hearing ' : 'Appointment ').$when.($event['time'] ? ' at '.$event['time'] : ''),
                body: collect([$event['title'], $event['case_number'], $event['court']])->filter()->unique()->implode(' · '),
                url: $this->relative($event['url']),
                key: "{$kind}:{$event['id']}:{$event['date']}",
            ));
        }

        if ($tomorrowHearings !== [] && now()->hour >= self::EVENING_HOUR) {
            $reminders->push($this->tomorrowSummary($tomorrowHearings, $today->copy()->addDay()));
        }

        $tasks = ! $user->preference('remind_tasks') ? collect() : Task::query()
            ->withoutGlobalScope('user_ownership')
            ->where('user_id', $user->workspaceId())
            ->where(fn ($query) => $query->whereNull('assigned_to')->orWhere('assigned_to', $user->id))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', $today->copy()->endOfDay())
            ->where('due_at', '>=', $today->copy()->subDays(30))
            ->get();

        foreach ($tasks as $task) {
            $isOverdue = $task->due_at->lt($today);

            $reminders->push(new ReminderNotification(
                kind: 'task',
                title: $isOverdue ? 'Task overdue since '.$task->due_at->format('d M') : 'Task due today',
                body: $task->title,
                url: route('tasks.show', $task, false),
                key: "task:{$task->id}:{$task->due_at->toDateString()}".($isOverdue ? ':overdue' : ''),
            ));
        }

        $invoices = ! $user->preference('remind_invoices') ? collect() : Invoice::query()
            ->withoutGlobalScope('user_ownership')
            ->with(['client', 'payments'])
            ->where('user_id', $user->workspaceId())
            ->whereNull('written_off_at')
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->get()
            ->filter(fn (Invoice $invoice): bool => $invoice->balanceDue() > 0);

        foreach ($invoices as $invoice) {
            $reminders->push(new ReminderNotification(
                kind: 'invoice',
                title: 'Invoice '.$invoice->invoice_number.' is overdue',
                body: collect([$invoice->client?->displayName(), 'due '.$invoice->due_date->format('d M Y')])->filter()->implode(' · '),
                url: route('invoices.show', $invoice, false),
                key: "invoice:{$invoice->id}:{$invoice->due_date->toDateString()}",
            ));
        }

        return $reminders;
    }

    /**
     * "Tomorrow: 3 hearings" with the first hearing's time and court, sent once in the evening.
     *
     * @param  list<array<string, mixed>>  $hearings
     */
    private function tomorrowSummary(array $hearings, Carbon $tomorrow): ReminderNotification
    {
        $sorted = collect($hearings)->sortBy(fn (array $event): string => $event['time'] ?? '99:99')->values();
        $first = $sorted->first();
        $count = $sorted->count();

        return new ReminderNotification(
            kind: 'hearing',
            title: 'Tomorrow: '.$count.' '.Str::plural('hearing', $count),
            body: collect([
                $first['time'] ? 'First at '.$first['time'] : null,
                $first['court'] ?? null,
                $sorted->pluck('title')->take(3)->implode(' · ').($count > 3 ? ' and '.($count - 3).' more' : ''),
            ])->filter()->implode(' · '),
            url: route('cases.calendar', ['view' => 'day', 'date' => $tomorrow->toDateString()], false),
            key: 'hearings-tomorrow:'.$tomorrow->toDateString(),
        );
    }

    /**
     * Keep only the path, query and fragment so links work on any domain the app runs on.
     */
    private function relative(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['path'] ?? '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
