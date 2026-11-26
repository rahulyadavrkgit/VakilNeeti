<?php

namespace Tests\Feature;

use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\HearingShareText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseSharingTest extends TestCase
{
    use RefreshDatabase;

    private LegalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 08:00:00');
        $this->actingAs(User::factory()->create());

        CourtBench::create([
            'court_key' => 'delhi-district-south-east',
            'court_room' => '612, main building',
            'judges' => [['name' => 'Sh. Akash Jain', 'designation' => 'District Judge-05']],
            'vc_link' => 'https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08',
        ]);

        $this->case = LegalCase::create([
            'case_title' => 'Prahlad vs Sanjay',
            'case_number' => 'CS/12/2026',
            'court_name' => 'District Judge - 05',
            'court_complex' => 'District and Sessions Judge, South-East, Saket, Delhi',
            'appearing_for' => 'petitioner',
            'case_status' => 'pending',
        ]);
        CourtHearing::create(['case_id' => $this->case->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '10:30', 'hearing_stage' => 'Final arguments']);

        LegalCase::create(['case_title' => 'No Side Yet', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
    }

    public function test_share_message_carries_side_time_court_room_judge_vc_link_and_purpose(): void
    {
        $text = HearingShareText::list('Hearings today', [HearingShareText::fromEvent([
            'title' => 'Prahlad vs Sanjay', 'case_number' => 'CS/12/2026', 'appearing_for' => 'petitioner',
            'date' => '2026-03-10', 'time' => '10:30', 'court' => 'District Judge - 05',
            'court_complex' => 'District and Sessions Judge, South-East, Saket, Delhi', 'details' => 'Final arguments',
        ])]);

        // In the order of the hearing cards: case and side; court, number and stage; judge; item and room; VC link.
        $this->assertSame(implode("\n", [
            '*Hearings today*',
            '',
            '1. Prahlad vs Sanjay (For Petitioner)',
            'District Judge - 05 · CS/12/2026 · Final arguments',
            'Sh. Akash Jain',
            'Court Room 612, main building · 10:30',
            'VC: https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08',
        ]), $text);
    }

    public function test_home_rows_offer_copy_vc_link_share_and_set_our_side(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-copy="https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08"', false)
            ->assertSee('title="Share this hearing"', false)->assertSee('title="Share all hearings of this day"', false)
            ->assertSee('We appear for: Petitioner', false);
    }

    public function test_case_list_shares_exactly_the_cases_shown_and_prompts_for_a_missing_side(): void
    {
        $html = $this->get(route('cases.index', ['hearing_filter' => 'today']))->assertOk()->getContent();

        $this->assertStringContainsString('Share 1 case', $html);
        $this->assertStringContainsString('*Cases · Today, Tue 10 Mar 2026*', html_entity_decode($html));
        $this->assertStringNotContainsString('No Side Yet', $html);

        $this->get(route('cases.index'))
            ->assertOk()
            ->assertSee('Set our side')
            ->assertSee('href="'.route('cases.edit', LegalCase::where('case_title', 'No Side Yet')->first()).'"', false);
    }

    public function test_calendar_offers_share_lists_for_the_view_today_tomorrow_and_next_7_days(): void
    {
        $response = $this->get(route('cases.calendar', ['view' => 'week', 'date' => '2026-03-10']))->assertOk();
        $lists = $response->viewData('shareLists');

        $this->assertSame(['view', 'today', 'tomorrow', 'next7'], array_keys($lists));
        $this->assertSame(1, $lists['today']['count']);
        $this->assertSame(0, $lists['tomorrow']['count']);
        $this->assertStringContainsString('Room 612, main building', $lists['today']['text']);
        $response->assertSee('Share hearings')->assertSee('Share Tue 10 Mar hearings', false);
    }
}
