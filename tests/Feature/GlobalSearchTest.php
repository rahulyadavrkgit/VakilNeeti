<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CourtBench;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_your_own_cases_and_clients_by_cnr_number_or_name(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Theirs vs State', 'cnr_number' => 'DLHC010000012026', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);

        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Asha vs Ravi', 'case_number' => 'CS/12/2024', 'cnr_number' => 'DLHC010215262023', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);
        Client::create(['client_code' => 1, 'client_type' => 'individual', 'first_name' => 'Asha', 'mobile' => '9876543210', 'status' => 'active']);

        $this->get(route('search', ['q' => 'DLHC0102']))->assertOk()->assertSee('Asha vs Ravi')->assertDontSee('Theirs vs State');
        $this->get(route('search', ['q' => '98765']))->assertOk()->assertSee('Asha');
        $this->get(route('search', ['q' => 'Delhi High']))->assertOk()->assertSee('Asha vs Ravi')->assertDontSee('Theirs vs State');
    }

    public function test_short_queries_and_wildcards_do_not_list_everything(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Asha vs Ravi', 'court_name' => 'X', 'case_status' => 'pending']);

        $this->get(route('search', ['q' => 'a']))->assertOk()->assertSee('Type at least 2 letters')->assertDontSee('Asha vs Ravi');
        $this->get(route('search', ['q' => '%%']))->assertOk()->assertDontSee('Asha vs Ravi');
    }

    public function test_results_go_straight_to_the_place_including_boards_and_court_pages(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Asha vs Ravi', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);
        $room = CourtBench::create(['court_key' => 'delhi-high-court', 'court_room' => '23', 'judges' => [['name' => "Hon'ble Mr. Justice Rajneesh Kumar Gupta"]]]);

        // While typing: each result opens its own page (no pop-up in between).
        $this->get(route('sheets.search', ['q' => 'Asha']))->assertOk()
            ->assertSee('href="'.route('cases.show', $case).'" data-search-result', false)
            ->assertDontSee('data-sheet=', false);
        $this->get(route('sheets.search', ['q' => 'Rajneesh']))->assertOk()
            ->assertSee('href="'.$room->pageUrl().'"', false);

        // A court's board opens already chosen; court pages and judges lists are found by name.
        $board = $this->get(route('sheets.search', ['q' => 'delhi high court boards']))->assertOk()
            ->assertSee('Pages &amp; boards', false)
            ->assertSee('Delhi High Court · Live display board')
            ->assertSee('href="'.route('courts.live-board', 'delhi-high-court').'"', false);
        $this->assertStringContainsString(e(route('courts.display-boards', ['board' => '']), false), $board->getContent());
        $this->get(route('sheets.search', ['q' => 'supreme court judges']))->assertOk()
            ->assertSee('href="'.route('courts.supreme-court.court-list').'"', false);
        $this->get(route('search', ['q' => 'tribunal']))->assertOk()
            ->assertSee('Pages &amp; boards', false)->assertSee('href="'.route('tribunals.index').'"', false);
    }

    public function test_the_desktop_header_search_shows_results_while_typing(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('cases.index'))->assertOk()
            ->assertSee('data-header-search class="relative', false)
            ->assertSee('data-header-search-input autocomplete="off"', false)
            ->assertSee('data-header-search-results hidden', false);
    }

    public function test_the_room_pop_up_shares_from_the_top_on_phones_and_the_bottom_on_wider_screens(): void
    {
        $this->actingAs(User::factory()->create());
        $room = CourtBench::create(['court_key' => 'delhi-district-north', 'court_room' => '320', 'judges' => [['name' => 'Ms. Garima Jindal', 'designation' => 'Judicial Magistrate First Class - 06']],
            'vc_link' => 'https://districtcourtdelhi.webex.com/meet/ddc.vc.north06', 'vc_meeting_id' => '176 040 0022', 'email' => 'mm06-north@ddc.nic.in']);

        $html = $this->get(route('sheets.court', ['bench' => $room->id]))->assertOk()->getContent();
        $message = e("*North District, Delhi (Rohini) · Room 320*\nMs. Garima Jindal, Judicial Magistrate First Class - 06\nVC: https://districtcourtdelhi.webex.com/meet/ddc.vc.north06\nMeeting ID: 176 040 0022\nEmail: mm06-north@ddc.nic.in\nJudges list: ".route('courts.delhi-district-courts.judges', 'north').'#room-'.$room->id);

        $this->assertSame(2, substr_count($html, 'data-share-now data-share-text="'.$message.'"'));
        $this->assertStringContainsString('aria-label="Share Room 320" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full border border-slate-200 text-[#16776e] hover:border-[#16776e] sm:hidden"', $html);
        $this->assertStringContainsString('sm:inline-flex">', $html);

        // Rooms have no page of their own: the pop-up and old room addresses open the room's card in the judges list.
        $this->assertStringContainsString('href="'.route('courts.delhi-district-courts.judges', 'north').'#room-'.$room->id.'"', $html);
        $this->get('/courts/room/'.$room->id.'/north-district-delhi-rohini-room-320-garima-jindal')
            ->assertRedirect(route('courts.delhi-district-courts.judges', 'north').'#room-'.$room->id);
        $this->get(route('courts.delhi-district-courts.judges', 'north'))->assertOk()
            ->assertSee('<article id="room-'.$room->id.'"', false)
            ->assertSee('href="#room-'.$room->id.'"', false);
    }

    public function test_search_needs_a_login(): void
    {
        $this->get(route('search', ['q' => 'abc']))->assertRedirect(route('login'));
    }
}
