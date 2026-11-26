<?php

namespace Tests\Feature;

use App\Models\CauseListAppearance;
use App\Models\CourtDocument;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\CauseLists\AllahabadHighCourtCauseList;
use App\Services\CauseLists\CauseListRecorder;
use App\Services\CauseLists\SupremeCourtCauseList;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourtCauseListsTest extends TestCase
{
    use RefreshDatabase;

    /** As the Supreme Court's PDF reads (shortened). */
    private const SUPREME_TEXT = <<<'TEXT'
DAILY CAUSE LIST FOR DATED : 26-11-2026
COURT NO. : 2
HON'BLE MR. JUSTICE VIKRAM NATH
HON'BLE MR. JUSTICE SANDEEP MEHTA
MISCELLANEOUS HEARING
1 C.A. No. 4392/2017
HEIRS OF THAKORE	SIDDHANT SHARMA- 3228
IA No. 354341/2026 - EARLY HEARING
2 C.A. No.
2403-2404/2019
HANAMANTH ETC.
2.1Connected
SLP(C) No. 28407/2026
COURT NO. : 2
3 CONMT.PET.(C) No.
826/2026 in C.A. No.
3870/2026
4Diary No. 47715-2026
RAJENDRA MEENA
TEXT;

    /** As one Allahabad court's PDF reads (shortened; items begin with a tab). */
    private const ALLAHABAD_TEXT = "Court No-90\nHON’BLE JUSTICE VANI RANJAN AGRAWAL\n26-11-2026 At 10:00 AM\nFRESH LIST (From Sr.No. 1 To Sr.No. 3)\n 1 \tRAM KUMAR\nLO BAIL/5221/2010\nJALAUN\nNotice No:\n2026/7630,A20260168549\nCourt No-9026-11-2026\n 2 \tSHYAM\nLO WRIC/742/2016\n 3 \tMOHAN\nLO WRIC/9/2020\n";

    public function test_supreme_court_list_gives_court_bench_items_and_skips_applications_and_parent_cases(): void
    {
        $entries = collect((new SupremeCourtCauseList)->entries(self::SUPREME_TEXT));

        $this->assertSame(['CA-4392-2017', 'CA-2403-2019', 'CA-2404-2019', 'SLPC-28407-2026', 'CONMTPETC-826-2026', 'DIARY-47715-2026'], $entries->pluck('key')->all());
        $this->assertSame(['1', '2', '2', '2.1', '3', '4'], $entries->pluck('item')->all());
        $this->assertSame(['MR. JUSTICE VIKRAM NATH', 'MR. JUSTICE SANDEEP MEHTA'], $entries->first()['judges']);

        // Position in the hearing order counts main items; connected 2.1 is heard with item 2.
        $place = CauseListRecorder::placeOnBoard(CauseListRecorder::boards($entries->all()), ['item' => '4', 'court' => '2']);
        $this->assertSame(['board_total' => 5, 'board_items' => 4, 'board_from' => '1', 'board_to' => '4', 'board_position' => 4], $place);

        $this->assertSame([['title' => 'Daily cause list M_J_1 · 26-11-2026', 'url' => 'https://api.sci.gov.in/jonew/cl/2026-11-26/M_J_1.pdf']],
            (new SupremeCourtCauseList)->documentsInPage('<a href="https://api.sci.gov.in/jonew/cl/2026-11-26/M_J_1.pdf">x</a><a href="https://api.sci.gov.in/jonew/cl/2026-11-25/M_J_1.pdf">y</a>', Carbon::parse('2026-11-26')));
    }

    public function test_allahabad_list_reads_the_court_even_when_the_page_header_runs_into_the_date(): void
    {
        $entries = collect((new AllahabadHighCourtCauseList)->entries(self::ALLAHABAD_TEXT));

        $this->assertSame(['BAIL-5221-2010', 'WRIC-742-2016', 'WRIC-9-2020'], $entries->pluck('key')->all());
        $this->assertSame(['90'], $entries->pluck('court')->unique()->values()->all());
        $this->assertSame(['JUSTICE VANI RANJAN AGRAWAL'], $entries->first()['judges']);
    }

    public function test_each_court_shows_the_facts_the_advocate_asked_for(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'case_status' => 'pending', 'court_name' => 'X']);
        foreach (['delhi-high-court', 'supreme-court', 'allahabad-high-court'] as $provider) {
            app(CauseListRecorder::class)->ensureSource($provider, $provider, 'https://example.test');
        }
        $listing = fn (string $provider, string $label, array $meta, string $item = '7', string $court = '4') => CauseListAppearance::create([
            'court_document_id' => CourtDocument::create(['provider_key' => $provider, 'document_key' => sha1($label.$provider), 'listing_date' => '2026-11-26', 'document_label' => $label, 'source_url' => 'https://example.test/'.sha1($label), 'metadata' => ['boards' => [$court => ['total' => $meta['board_total']]]]])->id,
            'legal_case_id' => $case->id, 'record_key' => sha1($label.$provider.$item), 'item_number' => $item, 'court_room' => $court, 'metadata' => $meta,
        ])->load('courtDocument');

        // Delhi HC supplementary list: which one, its item range, how many came out, and the board's total.
        $listing('delhi-high-court', 'Supplementary Cause List-1 of Sitting of Benches for 26.11.2026', ['board_total' => 3]);
        $delhi = $listing('delhi-high-court', 'Supplementary Cause List-3 of Sitting of Benches for 26.11.2026', ['board_total' => 16, 'board_from' => '60', 'board_to' => '75']);
        $this->assertSame(['Supplementary list 3', '2 supplementary lists published that day', 'items 60–75', '16 items in this supplementary list', "16 items on this court's board"], $delhi->boardFacts());

        // Supreme Court: place in the hearing order, and every case before that court that day.
        $listing('supreme-court', 'Daily cause list F_J_1 · 26-11-2026', ['board_total' => 40]);
        $supreme = $listing('supreme-court', 'Daily cause list M_J_1 · 26-11-2026', ['board_total' => 75, 'board_items' => 36, 'board_position' => 12]);
        $this->assertSame(['No. 12 of 36 in published hearing order', '75 cases before this court that day'], $supreme->boardFacts());

        $allahabad = $listing('allahabad-high-court', 'Combined Cause List · COURT NO.90', ['board_total' => 114]);
        $this->assertSame(['114 cases on this board'], $allahabad->boardFacts());
    }

    public function test_a_district_date_keeps_the_item_and_the_size_of_that_list(): void
    {
        $this->travelTo('2026-11-25 08:00:00');
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Ram vs Shyam', 'case_status' => 'pending', 'court_name' => 'Chief Judicial Magistrate', 'court_level' => 'district', 'state_code' => 'UP', 'district' => 'Jalaun']);

        $this->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-11-25', 'item_number' => '12', 'list_total' => 45, 'return' => 'back'])->assertSessionHasNoErrors();
        $this->assertSame(['12', 45], [CourtHearing::query()->value('item_number'), (int) CourtHearing::query()->value('list_total')]);

        $this->get(route('cases.index', ['hearing_filter' => 'today']))->assertOk()->assertSeeInOrder(['Ram vs Shyam', '12', '45']);
        $this->get(route('cases.show', $case))->assertOk()
            ->assertSee('My item: <strong class="text-sm">12</strong> of <strong>45</strong>', false)
            ->assertSee('Curr. item: <strong>N/A</strong>', false);

        $this->post(route('hearings.store'), ['case_id' => $case->id, 'hearing_date' => '2026-11-26', 'item_number' => 'twelve'])->assertSessionHasErrors('item_number');
    }

    public function test_cause_list_details_show_in_the_calendar(): void
    {
        $this->travelTo('2026-11-25 08:00:00');
        $this->actingAs(User::factory()->create());
        app(CauseListRecorder::class)->ensureSource('allahabad-high-court', 'Allahabad High Court', 'https://example.test');
        $case = LegalCase::create(['case_title' => 'Prahlad vs State of UP', 'case_number' => 'BAIL/5221/2010', 'case_status' => 'pending', 'court_name' => 'Allahabad High Court']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-11-26']);
        $list = CourtDocument::create(['provider_key' => 'allahabad-high-court', 'document_key' => 'c90', 'listing_date' => '2026-11-26', 'document_label' => 'Combined Cause List · COURT NO.90', 'source_url' => 'https://example.test/90.pdf']);
        CauseListAppearance::create(['court_document_id' => $list->id, 'legal_case_id' => $case->id, 'record_key' => 'r', 'item_number' => '97', 'court_room' => '90',
            'judge_name' => 'JUSTICE VANI RANJAN AGRAWAL', 'metadata' => ['board_total' => 114, 'board_items' => 114, 'board_position' => 97]]);

        foreach ([route('cases.calendar', ['view' => 'list', 'date' => '2026-11-26']), route('cases.calendar', ['view' => 'day', 'date' => '2026-11-26'])] as $page) {
            $this->get($page)->assertOk()
                ->assertSee('href="https://example.test/90.pdf"', false)
                ->assertDontSee('Allahabad HC cause list')
                ->assertSee('114 cases on this board')
                ->assertSeeInOrder(['Room 90', 'My item', '97', '114 cases on this board']);
        }
    }

    public function test_the_command_skips_courts_nobody_practises_in_and_is_scheduled(): void
    {
        Http::fake();
        $this->artisan('courts:cause-lists', ['court' => 'supreme-court'])->expectsOutputToContain('No saved Supreme Court of India cases')->assertSuccessful();
        Http::assertNothingSent();

        $commands = collect(app(Schedule::class)->events())->map(fn ($event) => (string) $event->command)->implode("\n");
        $this->assertStringContainsString('courts:cause-lists supreme-court', $commands);
        $this->assertStringContainsString('courts:cause-lists allahabad-high-court --when=tomorrow', $commands);
    }
}
