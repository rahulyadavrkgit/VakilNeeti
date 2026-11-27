<?php

namespace Tests\Feature;

use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\HearingShareText;
use App\Services\SupremeCourtBenches;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupremeCourtBenchesTest extends TestCase
{
    use RefreshDatabase;

    /** The court's page for Court 5 (shortened): the day's bench, then a special bench later in the day. */
    private const COURT_FIVE = <<<'HTML'
        <div class="table-responsive w-100">
            <b> [IT WILL BE APPRECIATED IF THE LEARNED ADVOCATES ON RECORD DO NOT SEEK ADJOURNMENT] </b><br> <br>
            DAILY CAUSE LIST FOR DATED: 27-11-2026 <br><br>
            COURT NO. : 5 <br><br>
            HON'BLE MR. JUSTICE PAMIDIGHANTAM SRI NARASIMHA<br> HON'BLE MR. JUSTICE ALOK ARADHE <br><br>
            (Time : 10:30 AM) <br><br>
            <b><u>MISCELLANEOUS HEARING</u></b>
            COURT NO. : 5 <br><br>
            HON'BLE MR. JUSTICE SPECIAL BENCH JUDGE <br><br>
            [SPECIAL BENCH]
        </div>
        HTML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-27 07:40:00');
        Storage::fake('local');
        SupremeCourtBenches::flush();
    }

    public function test_each_days_bench_is_saved_and_named_on_hearings_of_that_date_with_the_rooms_vc_link(): void
    {
        Http::fake(fn (Request $request) => Http::response(
            str_contains($request->url(), 'courtno=5') && str_contains($request->url(), 'cldate=2026-11-27') ? self::COURT_FIVE : '<html>No records</html>',
            404,
        ));
        CourtBench::create(['court_key' => 'supreme-court', 'court_room' => 'Court No. 5', 'vc_link' => 'https://sci-vc.webex.com/meet/court05', 'sort_order' => 5]);
        CourtBench::create(['court_key' => 'supreme-court', 'court_room' => 'Court No. 6', 'judges' => [['name' => 'Yesterday Judge']], 'sort_order' => 6]);

        $this->artisan('courts:supreme-court-benches')
            ->expectsOutputToContain('27 Nov 2026: 1 courts sitting.')
            ->expectsOutputToContain('28 Nov 2026: no benches listed yet.')
            ->assertSuccessful();

        // Only the room's own bench, not the special bench later that day.
        $both = "Hon'ble Mr. Justice Pamidighantam Sri Narasimha & Hon'ble Mr. Justice Alok Aradhe";
        $this->assertSame($both, SupremeCourtBenches::judgesOn('Supreme Court of India', 'Court No. 5', '2026-11-27'));
        $this->assertNull(SupremeCourtBenches::judgesOn('Supreme Court of India', '5', '2026-11-30'));
        $this->assertNull(HearingShareText::roomJudges('Supreme Court of India', '5', '2026-11-30', 'Roster Judge'));

        // Today's benches also fill the court list; a court not sitting today shows no one.
        $this->assertSame(["Hon'ble Mr. Justice Pamidighantam Sri Narasimha", "Hon'ble Mr. Justice Alok Aradhe"], array_column(CourtBench::query()->where('court_room', 'Court No. 5')->first()->judges, 'name'));
        $this->assertSame([], CourtBench::query()->where('court_room', 'Court No. 6')->first()->judges);

        $this->actingAs(User::factory()->create());
        $today = LegalCase::create(['case_title' => 'Today vs Bench', 'court_name' => 'Supreme Court of India', 'court_room' => '5', 'case_status' => 'pending']);
        $later = LegalCase::create(['case_title' => 'Later vs Bench', 'court_name' => 'Supreme Court of India', 'court_room' => '5', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $today->id, 'hearing_date' => '2026-11-27']);
        CourtHearing::create(['case_id' => $later->id, 'hearing_date' => '2026-11-30']);

        $html = $this->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder(['Today vs Bench', $both, 'href="https://sci-vc.webex.com/meet/court05"', 'Later vs Bench', 'href="https://sci-vc.webex.com/meet/court05"'])
            ->getContent();
        $this->assertSame(1, substr_count($html, e("Hon'ble Mr. Justice Alok Aradhe").'</p>'));

        $this->get(route('cases.show', $today))->assertOk()->assertSee($both);
    }

    public function test_a_rooms_cause_list_link_opens_the_courts_list_for_that_court_and_the_day_it_is_clicked(): void
    {
        // The court's own address, with the court number and date filled in (no date: the day it is opened).
        $this->get(route('courts.supreme-court.cause-list', ['court' => 2, 'date' => '2026-10-26']))
            ->assertRedirect('https://api.sci.gov.in/?pageid=900104&courtno=2&stage=Misc.%20Court&msb=both&cldate=2026-10-26');
        $this->get(route('courts.supreme-court.cause-list', 2))
            ->assertRedirect('https://api.sci.gov.in/?pageid=900104&courtno=2&stage=Misc.%20Court&msb=both&cldate=2026-11-27');
        $this->travelTo('2026-11-30 09:00:00');
        $this->get(route('courts.supreme-court.cause-list', ['court' => 2, 'date' => 'not-a-date']))
            ->assertRedirect('https://api.sci.gov.in/?pageid=900104&courtno=2&stage=Misc.%20Court&msb=both&cldate=2026-11-30');
        $this->get('/courts/supreme-court/cause-list/18')->assertNotFound();

        // A court that sat in its Regular list that day gets the Regular list.
        Storage::disk('local')->put(SupremeCourtBenches::FILE, json_encode(['2026-11-30' => ['3' => ['judges' => ['A'], 'time' => null, 'stage' => 'Regular Court']]]));
        SupremeCourtBenches::flush();
        $this->assertStringContainsString('courtno=3&stage=Regular%20Court&msb=both&cldate=2026-11-30', SupremeCourtBenches::listUrl(3, '2026-11-30'));

        // The court list offers it for each court room (not Registrar courts); hearing cards use the hearing's date.
        CourtBench::create(['court_key' => 'supreme-court', 'section' => 'Courts', 'court_room' => 'Court No. 5', 'vc_link' => 'https://sci-vc.webex.com/meet/court05', 'sort_order' => 1]);
        CourtBench::create(['court_key' => 'supreme-court', 'section' => 'Registrar courts', 'court_room' => 'R-01', 'vc_link' => 'https://sci-vc.webex.com/meet/registrarcourt01', 'sort_order' => 2]);
        $this->get(route('courts.supreme-court.court-list'))->assertOk()
            ->assertSee('href="'.route('courts.supreme-court.cause-list', 5).'"', false)
            ->assertSee("Today's cause list", false)
            ->assertDontSee(route('courts.supreme-court.cause-list', 1), false)
            // Each room can be shared: court and room, VC link and its cause list link.
            ->assertSee('data-share-text="'.e("*Supreme Court of India · Court No. 5*\nVC: https://sci-vc.webex.com/meet/court05\nToday's cause list: ".route('courts.supreme-court.cause-list', 5).'
Judges list: '.route('courts.supreme-court.court-list').'#court-5').'"', false);

        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Listed vs Later', 'court_name' => 'Supreme Court of India', 'court_room' => 'Court No. 5', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-12-02']);
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('href="'.e(route('courts.supreme-court.cause-list', ['court' => 5, 'date' => '2026-12-02'])).'"', false);
        $this->get(route('cases.show', $case))->assertOk()
            ->assertSee('href="'.e(route('courts.supreme-court.cause-list', ['court' => 5, 'date' => '2026-12-02'])).'"', false);
    }

    public function test_it_runs_every_morning_and_evening(): void
    {
        $this->assertTrue(collect(app(Schedule::class)->events())->contains(
            fn ($event): bool => str_contains($event->command, 'courts:supreme-court-benches') && $event->expression === '40 7,20,22 * * *',
        ));
    }
}
