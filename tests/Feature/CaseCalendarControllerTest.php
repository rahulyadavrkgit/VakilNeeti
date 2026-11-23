<?php

namespace Tests\Feature;

use App\Models\CaseAppointment;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    public function test_month_view_shows_your_hearings_appointments_and_tasks_only(): void
    {
        $this->actingAs(User::factory()->create());
        $otherCase = $this->makeCase(['case_title' => 'Other vs Advocate']);
        $this->hearing($otherCase, '2026-03-12');

        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();
        $this->hearing($case, '2026-03-05', ['next_hearing_date' => '2026-03-20']);
        CaseAppointment::create(['case_id' => $case->id, 'title' => 'Client meeting', 'starts_at' => '2026-03-11 15:00:00']);
        Task::create(['title' => 'File reply', 'due_at' => '2026-03-14 00:00:00', 'priority' => 'high', 'status' => 'pending']);

        $response = $this->get(route('cases.calendar', ['view' => 'month', 'date' => '2026-03-10']))
            ->assertOk()
            ->assertSee('March 2026')
            ->assertSee('Asha vs State')
            ->assertSee('Client meeting')
            ->assertSee('File reply')
            ->assertDontSee('Other vs Advocate');

        $this->assertSame(['hearing' => 2, 'appointment' => 1, 'task' => 1], $response->viewData('counts'));
        $this->assertSame(['next_hearing'], $response->viewData('eventsByDate')->get('2026-03-20')->pluck('type')->all());
    }

    public function test_month_events_carry_a_hover_card_with_side_court_room_judge_and_vc_link(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase([
            'appearing_for' => 'respondent',
            'court_room' => '304, Main court building',
            'judge_name' => 'Ms. Niti Phutela',
            'vc_link' => 'https://districtcourtdelhi.webex.com/meet/ddc.vc.south02',
        ]);
        $this->hearing($case, '2026-03-12', ['hearing_stage' => 'Final arguments']);

        $html = $this->get(route('cases.calendar', ['view' => 'month', 'date' => '2026-03-10']))->assertOk()->getContent();

        $this->assertStringContainsString('data-event-peek', $html);
        $card = substr($html, (int) strpos($html, '<template data-event-card>'), (int) strpos($html, '</template>') - (int) strpos($html, '<template data-event-card>'));
        foreach (['Thu, 12 Mar 2026', 'Asha vs State', 'Respondent', 'Room 304, Main court building', 'Ms. Niti Phutela', 'href="https://districtcourtdelhi.webex.com/meet/ddc.vc.south02"', 'Final arguments', 'Open case'] as $detail) {
            $this->assertStringContainsString($detail, $card);
        }
    }

    public function test_day_and_week_views_only_cover_their_own_dates(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase();
        $this->hearing($case, '2026-03-10', ['hearing_stage' => 'Arguments today']);
        $this->hearing($case, '2026-03-16', ['hearing_stage' => 'Evidence next week']);

        // The day shows only its own hearings (the "Share list" menu may still offer the next 7 days).
        $day = $this->get(route('cases.calendar', ['view' => 'day', 'date' => '2026-03-10']))
            ->assertOk()->assertSee('Tuesday, 10 March 2026')->assertSee('Arguments today');
        $this->assertSame(['Arguments today'], $day->viewData('events')->pluck('details')->all());

        $week = $this->get(route('cases.calendar', ['view' => 'week', 'date' => '2026-03-10']))->assertOk();
        $this->assertSame('2026-03-08', $week->viewData('start')->toDateString());
        $this->assertSame('2026-03-14', $week->viewData('end')->toDateString());
        $this->assertCount(1, $week->viewData('events'));
        $this->assertSame('2026-03-17', $week->viewData('nextDate'));
    }

    public function test_court_and_type_filters_narrow_the_events(): void
    {
        $this->actingAs(User::factory()->create());
        $lucknow = $this->makeCase();
        $delhi = $this->makeCase(['case_title' => 'Delhi matter', 'court_name' => 'Delhi High Court']);
        $this->hearing($lucknow, '2026-03-12');
        $this->hearing($delhi, '2026-03-12');
        Task::create(['title' => 'Unlinked task', 'due_at' => '2026-03-12 10:00:00', 'priority' => 'low', 'status' => 'pending']);

        $byCourt = $this->get(route('cases.calendar', ['view' => 'list', 'date' => '2026-03-10', 'court' => 'Delhi High Court']))->assertOk();
        $this->assertSame(['Delhi matter'], $byCourt->viewData('events')->pluck('title')->all());

        $hearingsOnly = $this->get(route('cases.calendar', ['view' => 'list', 'date' => '2026-03-10', 'types' => ['hearing']]))->assertOk();
        $this->assertSame(['hearing'], $hearingsOnly->viewData('events')->pluck('group')->unique()->values()->all());
        $hearingsOnly->assertDontSee('Unlinked task');

        $this->assertSame(['Delhi High Court', 'District Court Lucknow'], $byCourt->viewData('filterOptions')['courts']);
    }

    public function test_bad_or_old_style_parameters_fall_back_safely(): void
    {
        $this->actingAs(User::factory()->create());

        $invalid = $this->get(route('cases.calendar', ['view' => 'year', 'date' => '2026-02-31']))->assertOk();
        $this->assertSame('month', $invalid->viewData('viewMode'));
        $this->assertSame('2026-03-10', $invalid->viewData('date')->toDateString());

        $legacy = $this->get(route('cases.calendar', ['view' => 'agenda', 'month' => '2025-12']))->assertOk();
        $this->assertSame('list', $legacy->viewData('viewMode'));
        $this->assertSame('December 2025', $legacy->viewData('periodLabel'));
    }

    public function test_print_list_opens_the_print_dialog(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('cases.calendar', ['view' => 'list', 'print' => 1]))->assertOk()->assertSee('data-autoprint', false);
        $this->get(route('cases.calendar'))->assertOk()->assertDontSee('data-autoprint', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCase(array $overrides = []): LegalCase
    {
        return LegalCase::create(array_merge([
            'case_title' => 'Asha vs State',
            'case_number' => 'CS/12/2026',
            'court_name' => 'District Court Lucknow',
            'case_status' => 'pending',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function hearing(LegalCase $case, string $date, array $overrides = []): CourtHearing
    {
        return CourtHearing::create(array_merge(['case_id' => $case->id, 'hearing_date' => $date], $overrides));
    }
}
