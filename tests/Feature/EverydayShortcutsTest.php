<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EverydayShortcutsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_my_cases_has_one_add_button_and_keeps_the_rest_in_one_menu(): void
    {
        LegalCase::create(['case_title' => 'Ram vs Shyam', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        $html = $this->get(route('cases.index'))->assertOk()
            ->assertSee('data-shortcut="n"', false)
            ->assertSee('aria-label="More actions"', false)
            ->assertSee('Import from eCourts')
            ->assertSee('Copy this list')
            ->assertSee('Share 1 case')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'data-shortcut="n"'));
        // A slim line points to the next thing to do: the case has no hearing date yet.
        $this->assertStringContainsString('1 case without a hearing date', $html);
    }

    public function test_calendar_opens_as_a_list_on_phones_unless_a_view_was_chosen(): void
    {
        $this->get(route('cases.calendar'))->assertOk()
            ->assertSee("matchMedia('(max-width: 639px)')", false);

        $this->get(route('cases.calendar', ['view' => 'month']))->assertOk()
            ->assertDontSee("matchMedia('(max-width: 639px)')", false);

        $this->user->forceFill(['preferences' => ['calendar_view' => 'week']])->save();
        $this->get(route('cases.calendar'))->assertOk()
            ->assertDontSee("matchMedia('(max-width: 639px)')", false);
    }

    public function test_case_page_has_a_phone_action_bar_that_calls_the_client(): void
    {
        $case = LegalCase::create(['case_title' => 'Ram vs Shyam', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        $this->get(route('cases.show', $case))->assertOk()
            ->assertSee('aria-label="Case actions"', false)
            ->assertSee('Add client')
            ->assertSee(route('cases.download', $case), false);

        $client = Client::create(['client_type' => 'individual', 'first_name' => 'Ravi', 'mobile' => '98765 43210']);
        $case->parties()->create(['client_id' => $client->id, 'party_name' => 'Ravi', 'is_client' => true]);

        $this->get(route('cases.show', $case))->assertOk()
            ->assertSee('href="tel:+919876543210"', false)
            ->assertSee('Call client');
    }

    public function test_empty_alerts_and_tasks_say_what_to_do_next(): void
    {
        $this->get(route('notifications.index'))->assertOk()
            ->assertSee('No alerts yet')
            ->assertSee(e(route('cases.index', ['hearing_filter' => 'no_upcoming'])), false)
            ->assertSee('Reminder settings');

        $this->get(route('tasks.index'))->assertOk()
            ->assertSee('+ Add a task')
            ->assertSee('data-shortcut="n"', false);
    }
}
