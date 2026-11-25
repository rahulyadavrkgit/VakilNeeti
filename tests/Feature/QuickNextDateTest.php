<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickNextDateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
        $this->actingAs(User::factory()->create());
    }

    public function test_cases_needing_a_date_offer_add_next_date_and_saving_returns_to_the_list(): void
    {
        $case = LegalCase::create(['case_title' => 'Dated Long Ago', 'court_name' => 'Saket', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-02-01']);
        $upcoming = LegalCase::create(['case_title' => 'Has A Date', 'court_name' => 'Saket', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $upcoming->id, 'hearing_date' => '2026-03-20']);

        $html = $this->get(route('cases.index'))->assertOk()->getContent();
        // "Add next date" opens the next-date pop-up for that case only.
        $this->assertSame(1, substr_count($html, 'data-sheet="'.route('sheets.next-date', ['case' => $case->id]).'"'));
        $this->assertStringNotContainsString('data-sheet="'.route('sheets.next-date', ['case' => $upcoming->id]).'"', $html);
        $this->get(route('sheets.next-date', ['case' => $case->id]))
            ->assertOk()
            ->assertSee('name="case_id" value="'.$case->id.'"', false)
            ->assertSee('data-sheet-form', false);

        $this->from(route('cases.index', ['hearing_filter' => 'past']))
            ->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-03-18', 'hearing_time' => '11:00', 'hearing_stage' => 'Arguments', 'return' => 'back'])
            ->assertRedirect(route('cases.index', ['hearing_filter' => 'past']))
            ->assertSessionHas('success', 'Hearing added.');

        $this->assertDatabaseHas('court_hearings', ['case_id' => $case->id, 'hearing_stage' => 'Arguments']);
        $this->assertSame('2026-03-18', $case->hearings()->latest('id')->first()->next_hearing_date->toDateString());
    }

    public function test_case_page_opens_with_the_next_hearing_panel(): void
    {
        $case = LegalCase::create(['case_title' => 'Meera vs Union', 'court_name' => 'Delhi High Court', 'court_room' => '37', 'judge_name' => 'Justice Mehta', 'appearing_for' => 'appellant', 'vc_link' => 'https://dhc.webex.com/meet/court37', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-09', 'next_hearing_date' => '2026-03-11']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-11', 'hearing_time' => '10:30', 'hearing_stage' => 'Final arguments', 'item_number' => '12', 'list_total' => 34]);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSeeInOrder(['For', 'Appellant', 'Share case details', 'aria-label="Next hearing"', 'Wednesday, 11 March 2026, 10:30', 'Purpose: Final arguments', 'Room 37', 'Justice Mehta', 'Join VC', 'Copy VC link', 'My item', '12', 'Cases in list', '34', 'Add another date', 'aria-label="Case workspace"'], false)
            ->assertSee('Item: 12')->assertSee('Cases in list: 34');

        $noDate = LegalCase::create(['case_title' => 'Never Listed', 'court_name' => 'Saket', 'case_status' => 'pending']);
        $this->get(route('cases.show', $noDate))->assertOk()->assertSee('No hearing date yet. Add the first date below.')->assertSee('Set our side');
    }

    public function test_pages_share_one_header_style(): void
    {
        foreach (['tasks.index' => 'Tasks &amp; Reminders', 'clients.index' => 'Clients', 'profile.index' => 'Settings', 'billing.index' => 'Billing'] as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee('<h1 class="text-2xl font-bold tracking-tight text-slate-900">'.$title.'</h1>', false);
        }
    }
}
