<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Creates the bell reminders: hearings and appointments today or tomorrow,
 * tasks due today or overdue, and overdue invoices. Each reminder is sent once.
 */
class ReminderService
{
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

        $events = $this->calendar->between($today, $today->copy()->addDay(), [
            'groups' => ['hearing', 'appointment'],
            'user_id' => $user->id,
        ]);

        foreach ($events as $event) {
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

        $tasks = Task::query()
            ->withoutGlobalScope('user_ownership')
            ->where('user_id', $user->id)
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

        $invoices = Invoice::query()
            ->withoutGlobalScope('user_ownership')
            ->with(['client', 'payments'])
            ->where('user_id', $user->id)
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
