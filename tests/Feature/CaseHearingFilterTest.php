<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseHearingFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
        $this->actingAs(User::factory()->create());

        $case = fn (string $title) => LegalCase::create(['case_title' => $title, 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        // Added by hand for today: only the hearing's own date is set.
        CourtHearing::create(['case_id' => $case('Own Date Today')->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '10:30']);
        // Fixed at the last hearing for tomorrow.
        CourtHearing::create(['case_id' => $case('Next Date Tomorrow')->id, 'hearing_date' => '2026-02-20', 'next_hearing_date' => '2026-03-11']);
        // The last day of the next 7 days (today + 6).
        CourtHearing::create(['case_id' => $case('Sixth Day')->id, 'hearing_date' => '2026-03-16']);
        // Beyond next week.
        CourtHearing::create(['case_id' => $case('Later This Month')->id, 'next_hearing_date' => '2026-03-25']);
        // Every date has passed.
        CourtHearing::create(['case_id' => $case('All Dates Passed')->id, 'hearing_date' => '2026-02-01', 'next_hearing_date' => '2026-03-01']);
        // Never had a date.
        $case('Never Listed');
    }

    public function test_each_chip_shows_every_case_with_a_hearing_on_its_days_whichever_date_holds_it(): void
    {
        $shows = fn (string $filter) => collect($this->get(route('cases.index', ['hearing_filter' => $filter]))->assertOk()->viewData('cases')->items())
            ->pluck('case_title')->sort()->values()->all();

        $this->assertSame(['Own Date Today'], $shows('today'));
        $this->assertSame(['Next Date Tomorrow'], $shows('tomorrow'));
        $this->assertSame(['Next Date Tomorrow', 'Own Date Today', 'Sixth Day'], $shows('next7'));
        $this->assertSame(['All Dates Passed'], $shows('past'));
        $this->assertSame(['Never Listed'], $shows('no_upcoming'));
    }

    public function test_chips_show_their_counts_and_next_30_days_has_no_chip(): void
    {
        $response = $this->get(route('cases.index'))->assertOk();

        $this->assertSame(['all' => 6, 'today' => 1, 'tomorrow' => 1, 'next7' => 3, 'past' => 1, 'no_upcoming' => 1], $response->viewData('hearingCounts'));
        $response->assertDontSee('Next 30 days')->assertSee('No date yet')->assertSee('title="Cases with a hearing from today to 16 Mar"', false);

        // Old links with the 30-day filter still work.
        $this->get(route('cases.index', ['hearing_filter' => 'next30']))->assertOk()->assertSee('Later This Month');
    }

    public function test_the_date_tile_uses_the_nearest_date_and_its_time(): void
    {
        $this->get(route('cases.index', ['hearing_filter' => 'today']))
            ->assertOk()
            ->assertSeeInOrder(['Today', '10', '10:30', 'Own Date Today']);
    }
}
