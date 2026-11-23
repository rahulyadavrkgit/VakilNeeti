<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCourtsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    public function test_courts_page_opens_on_your_own_courts_soonest_first(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other);
        LegalCase::create(['case_title' => 'Someone else', 'court_name' => 'Family Court Pune', 'case_status' => 'pending']);

        $this->actingAs(User::factory()->create());
        $later = LegalCase::create(['case_title' => 'Ravi vs Mohan', 'court_name' => 'District Court Lucknow', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $later->id, 'hearing_date' => '2026-03-01', 'next_hearing_date' => '2026-03-14']);
        $today = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Delhi High Court', 'court_room' => '12', 'judge_name' => 'Hon. Justice A', 'vc_link' => 'https://vc.example/12', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $today->id, 'hearing_date' => '2026-03-10']);
        LegalCase::create(['case_title' => 'Second HC matter', 'court_name' => 'delhi high court ', 'case_status' => 'pending']);
        LegalCase::create(['case_title' => 'Old matter', 'court_name' => 'Tis Hazari', 'case_status' => 'closed', 'closed_at' => now()]);

        $response = $this->get(route('courts.index'))
            ->assertOk()
            ->assertSee('Your courts')
            ->assertSeeInOrder(['id="my-courts-heading"', 'Delhi High Court', 'Today', '1 case', 'Room 12', 'Hon. Justice A', 'Join VC', '2 open cases', 'District Court Lucknow', 'Sat, 14 Mar', '1 open case', 'id="court-services-heading"'], false)
            ->assertDontSee('Family Court Pune')
            ->assertDontSee('Old matter')
            ->assertSee(route('cases.index', ['search' => 'Delhi High Court', 'search_type' => 'court']))
            ->assertSee(route('courts.delhi-high-court'), false);

        $this->assertSame(['Delhi High Court', 'District Court Lucknow'], $response->viewData('myCourts')->pluck('court')->all());
        $this->assertSame([1, 0], $response->viewData('myCourts')->pluck('today')->all());
    }

    public function test_without_cases_the_page_offers_to_add_one_and_guests_see_only_services(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('courts.index'))
            ->assertOk()
            ->assertSee('The courts of your open cases will show here')
            ->assertSee(route('cases.create'), false);

        auth()->logout();

        $this->get(route('courts.index'))
            ->assertOk()
            ->assertDontSee('Your courts')
            ->assertSee('Court services');
    }
}
