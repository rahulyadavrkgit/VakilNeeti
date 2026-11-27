<?php

namespace Tests\Feature;

use App\Http\Controllers\CourtLinksController;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\LiveCourtBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveCourtBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 11:00:00');
        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_supreme_court_board_is_read_from_the_courts_own_data(): void
    {
        $this->fakeCourts();

        $board = app(LiveCourtBoard::class)->board('supreme-court');

        $this->assertTrue($board['ok']);
        $this->assertSame('Supreme Court', $board['short']);
        $this->assertSame([
            'room' => '3', 'label' => 'Court 3', 'status' => 'running', 'item' => '30',
            'case_number' => 'SLP(C) No. 39493/2026', 'parties' => 'Gurmeet Kaur vs Honey Chahal', 'message' => 'Seq 1 to 22',
        ], $board['rooms'][1]);
        $this->assertSame(['room' => '1', 'label' => 'Court 1', 'status' => 'not_in_session', 'item' => null, 'case_number' => null, 'parties' => null, 'message' => null], $board['rooms'][0]);
        $this->assertSame('Registrar Court 1', $board['rooms'][2]['label']);

        // One fetch serves everyone within the refresh window.
        app(LiveCourtBoard::class)->board('supreme-court');
        Http::assertSentCount(1);
    }

    public function test_delhi_high_court_board_follows_the_courts_legend(): void
    {
        $this->fakeCourts();

        $rooms = collect(app(LiveCourtBoard::class)->board('delhi-high-court')['rooms'])->keyBy('room');

        $this->assertCount(60, $rooms);
        $this->assertSame(['running', 'A27'], [$rooms['12']['status'], $rooms['12']['item']]);
        $this->assertSame('not_in_session', $rooms['1']['status']);
        $this->assertSame('exhausted', $rooms['4']['status']);
        $this->assertSame('R1', $rooms['52']['label']);
        $this->assertSame('not_in_session', $rooms['59']['status']);
    }

    public function test_when_the_court_does_not_answer_the_last_good_board_is_kept(): void
    {
        Http::fake(['delhihighcourt.nic.in/*' => Http::sequence()
            ->push(['all_courts' => ['12' => 'A27']])
            ->push('down', 503)]);
        $this->withoutDefer();
        $this->assertTrue(app(LiveCourtBoard::class)->board('delhi-high-court')['ok']);

        // A few seconds on, the copy is still current: the court is not asked again.
        $this->travel(3)->seconds();
        $this->assertTrue(app(LiveCourtBoard::class)->board('delhi-high-court')['ok']);
        Http::assertSentCount(1);

        // Later the court is asked again (and does not answer): the last good board is shown, marked as not current.
        $this->travel(1)->minutes();
        $board = app(LiveCourtBoard::class)->board('delhi-high-court');

        $this->assertFalse($board['ok']);
        $this->assertSame('A27', collect($board['rooms'])->firstWhere('room', '12')['item']);
    }

    public function test_an_unreachable_court_is_not_asked_again_for_a_while_and_its_live_board_is_not_offered(): void
    {
        Http::fake(['cdb.sci.gov.in/*' => Http::response('', 504)]);
        $this->withoutDefer();

        $this->assertSame([], app(LiveCourtBoard::class)->board('supreme-court')['rooms']);
        $this->assertFalse(app(LiveCourtBoard::class)->isAvailable('supreme-court'));
        $this->assertNotContains(route('courts.live-board', 'supreme-court'), collect(CourtLinksController::boardOptions())->pluck('url'));
        $this->assertContains(route('courts.live-board', 'delhi-high-court'), collect(CourtLinksController::boardOptions())->pluck('url'));

        $this->travel(10)->seconds();
        app(LiveCourtBoard::class)->board('supreme-court');
        Http::assertSentCount(1);

        $this->get(route('courts.live-board', 'supreme-court'))->assertOk()->assertSee("Open the court's board", false);

        $this->travel(25)->seconds();
        app(LiveCourtBoard::class)->board('supreme-court');
        Http::assertSentCount(2);
    }

    public function test_live_data_is_uncached_and_recovers_without_reloading_the_board_page(): void
    {
        Http::fake(['delhihighcourt.nic.in/app/courtdata' => Http::sequence()
            ->push('unavailable', 503)
            ->push(['all_courts' => ['23' => '66']])
            ->push(['all_courts' => ['23' => '67']])]);
        $this->get(route('courts.live-board', 'delhi-high-court'))
            ->assertOk()
            ->assertSee('data-live-unavailable', false);

        $url = route('courts.live-board.data', 'delhi-high-court');
        $this->travel(31)->seconds();
        $this->getJson($url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('ok', true)
            ->assertJsonPath('rooms.22.item', '66');

        $this->travel(10)->seconds();
        $this->getJson($url)->assertOk()->assertJsonPath('rooms.22.item', '67');
        Http::assertSentCount(3);
    }

    public function test_court_rooms_are_matched_to_board_rooms(): void
    {
        $this->assertSame('delhi-high-court', LiveCourtBoard::boardFor('High Court of Delhi'));
        $this->assertSame('supreme-court', LiveCourtBoard::boardFor('Supreme Court of India'));
        $this->assertNull(LiveCourtBoard::boardFor('District Court Lucknow'));

        $this->assertSame('12', LiveCourtBoard::roomKey('delhi-high-court', 'Court No. 12'));
        $this->assertSame('7', LiveCourtBoard::roomKey('supreme-court', 'C-07'));
        $this->assertSame('54', LiveCourtBoard::roomKey('delhi-high-court', 'R-3'));
        $this->assertSame('22', LiveCourtBoard::roomKey('supreme-court', 'RC 2'));
        $this->assertNull(LiveCourtBoard::roomKey('delhi-high-court', 'Court hall'));
    }

    public function test_board_page_and_data_are_public_and_outline_your_courts_today(): void
    {
        $this->fakeCourts();

        $this->get(route('courts.live-board', 'supreme-court'))
            ->assertOk()
            ->assertSee('Supreme Court · Live board')
            ->assertSee('Gurmeet Kaur vs Honey Chahal')
            ->assertSee('cdb.sci.gov.in')
            ->assertDontSee('Your courts today');

        $this->getJson(route('courts.live-board.data', 'delhi-high-court'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('rooms.11.item', 'A27');

        $this->get('/courts/live-board/allahabad-high-court')->assertNotFound();

        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'High Court of Delhi', 'court_room' => 'Court No. 12', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '10:30:00']);

        $this->get(route('courts.live-board', 'delhi-high-court'))
            ->assertOk()
            ->assertSee('Your courts today:')
            ->assertSeeInOrder(['data-room="12"', 'data-mine', 'Yours', 'A27', 'Asha vs State · 10:30'], false);
    }

    public function test_board_viewer_offers_the_live_boards_first_inside_vakilniti(): void
    {
        $options = collect(CourtLinksController::boardOptions());

        $this->assertSame(['Live board in VakilNiti', true], [$options[0]['title'], $options[0]['embeddable']]);
        $this->assertSame(route('courts.live-board', 'supreme-court'), $options[0]['url']);
        $this->assertSame(route('courts.live-board', 'delhi-high-court'), $options->firstWhere('court', 'High Court of Delhi')['url']);

        $this->get(route('courts.display-boards'))
            ->assertOk()
            ->assertSee('<option value="'.route('courts.live-board', 'delhi-high-court').'" data-embeddable="1"', false);
    }

    public function test_todays_hearings_show_a_live_chip_for_their_room_and_other_days_do_not(): void
    {
        $this->actingAs(User::factory()->create());
        $today = LegalCase::create(['case_title' => 'Asha vs State', 'case_number' => 'W.P.(C) 345/2026', 'court_name' => 'High Court of Delhi', 'court_room' => '12', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $today->id, 'hearing_date' => '2026-03-10']);
        $later = LegalCase::create(['case_title' => 'Ravi vs Union', 'court_name' => 'Supreme Court of India', 'court_room' => '5', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $later->id, 'hearing_date' => '2026-03-12']);

        $this->get(route('cases.calendar', ['view' => 'list', 'date' => '2026-03-10']))
            ->assertOk()
            ->assertSeeInOrder(['data-live-chip data-live-item-only', 'data-source="'.route('courts.live-board.data', 'delhi-high-court').'" data-room="12" data-case-number="W.P.(C) 345/2026"'], false)
            ->assertDontSee('data-source="'.route('courts.live-board.data', 'supreme-court').'"', false);

        Http::assertNothingSent();
    }

    private function fakeCourts(): void
    {
        $courtData = array_fill_keys(array_map('strval', range(1, 60)), '*');
        $courtData['12'] = 'A27';
        $courtData['4'] = 'X';

        Http::fake([
            'cdb.sci.gov.in/*' => Http::response(['listedItemDetails' => [
                ['court_no' => '1', 'court_name' => '<font class="text-muted">C1</font>', 'item_no' => '<font class="text-muted">&nbsp;</font>', 'registration_number_display' => 'Not in Session', 'petitioner_name' => '&nbsp;', 'respondent_name' => '&nbsp;', 'item_status' => '', 'court_message' => ''],
                ['court_no' => 3, 'court_name' => 'C3', 'item_no' => '30', 'registration_number_display' => 'SLP(C) No. 39493/2026', 'petitioner_name' => 'gurmeet kaur ', 'respondent_name' => ' honey chahal', 'item_status' => 'HEARING', 'court_message' => 'Seq 1 to 22'],
                ['court_no' => '21', 'court_name' => '<font class="text-muted">RC1</font>', 'item_no' => '&nbsp;', 'registration_number_display' => 'Not in Session', 'petitioner_name' => '&nbsp;', 'respondent_name' => '&nbsp;', 'item_status' => '', 'court_message' => ''],
            ]]),
            'delhihighcourt.nic.in/app/courtdata' => Http::response(['all_courts' => $courtData]),
        ]);
    }
}
