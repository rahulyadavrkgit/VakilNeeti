<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CourtHearing;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemindersAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    public function test_reminders_cover_hearings_tasks_and_overdue_invoices_and_are_sent_once(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other);
        $otherCase = LegalCase::create(['case_title' => 'Other vs Advocate', 'court_name' => 'District Court Lucknow', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $otherCase->id, 'hearing_date' => '2026-03-11']);

        $user = User::factory()->create();
        $this->actingAs($user);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'case_number' => 'CS/12/2026', 'court_name' => 'District Court Lucknow', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-11', 'hearing_time' => '10:30:00']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-20']);
        Task::create(['title' => 'File reply', 'due_at' => '2026-03-08 10:00:00', 'priority' => 'high', 'status' => 'pending']);
        Task::create(['title' => 'Finished task', 'due_at' => '2026-03-08 10:00:00', 'priority' => 'high', 'status' => 'completed']);
        $this->invoice('INV-1', '2026-03-01');
        $this->invoice('INV-2', '2026-03-30');

        // Morning: tasks and invoices; tomorrow's hearings wait for the evening summary.
        $this->assertSame(2, app(ReminderService::class)->sendFor($user));

        $this->travelTo('2026-03-10 18:00:00');
        $this->assertSame(1, app(ReminderService::class)->sendFor($user));
        $this->assertSame(0, app(ReminderService::class)->sendFor($user));

        $titles = $user->notifications()->get()->pluck('data.title')->sort()->values()->all();
        $this->assertSame(['Invoice INV-1 is overdue', 'Task overdue since 08 Mar', 'Tomorrow: 1 hearing'], $titles);
        $this->assertSame('First at 10:30 · District Court Lucknow · Asha vs State', $user->notifications()->get()->firstWhere('data.title', 'Tomorrow: 1 hearing')->data['body']);
        $this->assertSame(0, $other->notifications()->count());
    }

    public function test_the_reminder_command_runs_for_every_user_without_a_signed_in_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'District Court Lucknow', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-10']);
        auth()->logout();

        $this->artisan('reminders:send')->expectsOutput('Sent 1 reminders.')->assertSuccessful();

        $this->assertSame('Hearing today', $user->notifications()->first()->data['title']);
    }

    public function test_bell_shows_unread_count_and_opening_marks_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->notify(new ReminderNotification('task', 'Task due today', 'File reply', '/tasks', 'task:1'));
        $user->notify(new ReminderNotification('task', 'Bad link', 'x', 'https://evil.example/phish', 'task:2'));

        $this->get(route('cases.calendar'))->assertOk()->assertSee('Notifications, 2 unread')->assertSee('Task due today');

        $first = $user->notifications()->where('data->key', 'task:1')->firstOrFail();
        $this->get(route('notifications.open', $first->id))->assertRedirect('/tasks');
        $this->assertNotNull($first->fresh()->read_at);

        $bad = $user->notifications()->where('data->key', 'task:2')->firstOrFail();
        $this->get(route('notifications.open', $bad->id))->assertRedirect(route('notifications.index'));
    }

    public function test_cannot_open_another_users_notification_and_mark_all_read_clears_the_count(): void
    {
        $other = User::factory()->create();
        $other->notify(new ReminderNotification('task', 'Theirs', 'x', '/tasks', 'task:9'));

        $user = User::factory()->create();
        $user->notify(new ReminderNotification('task', 'Mine', 'x', '/tasks', 'task:1'));
        $this->actingAs($user);

        $this->get(route('notifications.open', $other->notifications()->first()->id))->assertNotFound();

        $this->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());

        $this->get(route('notifications.index'))->assertOk()->assertSee('Mine')->assertDontSee('Theirs');
    }

    private function invoice(string $number, string $dueDate): Invoice
    {
        $client = Client::create(['client_code' => Client::nextCode(), 'client_type' => 'individual', 'first_name' => 'Ravi', 'status' => 'active']);

        return Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => $number,
            'invoice_date' => '2026-02-01',
            'due_date' => $dueDate,
            'currency' => 'INR',
            'subtotal' => 1000,
            'discount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => 'sent',
        ]);
    }
}
