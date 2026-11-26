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

    public function test_it_runs_every_morning_and_evening(): void
    {
        $this->assertTrue(collect(app(Schedule::class)->events())->contains(
            fn ($event): bool => str_contains($event->command, 'courts:supreme-court-benches') && $event->expression === '40 7,20,22 * * *',
        ));
    }
}
