<?php

namespace Tests\Feature;

use App\Models\CourtBench;
use Database\Seeders\CourtBenchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CourtRoomListTest extends TestCase
{
    use RefreshDatabase;

    public function test_supreme_court_list_shows_rooms_with_join_vc_todays_cause_list_and_share(): void
    {
        CourtBench::create(['court_key' => 'supreme-court', 'court_room' => '1', 'vc_link' => 'https://sci-vc.webex.com/meet/court01']);

        $html = $this->get(route('courts.supreme-court.court-list'))
            ->assertOk()
            ->assertSee('<title>Supreme Court of India: Court list | VakilNiti</title>', false)
            ->assertSee('data-filter-list="court-rooms"', false)
            ->assertSee('data-copy="https://sci-vc.webex.com/meet/court01"', false)
            ->getContent();

        $list = substr($html, (int) strpos($html, 'id="court-rooms"'), (int) strpos($html, 'data-filter-empty') - (int) strpos($html, 'id="court-rooms"'));
        // Outside VakilNiti: Join VC, the court's list for today, and sharing the room.
        $this->assertSame(3, substr_count($list, 'target="_blank"'));
        $this->assertStringContainsString('href="'.route('courts.supreme-court.cause-list', 1).'" target="_blank"', $list);
        $this->assertStringContainsString("Today's cause list", $list);
        $this->assertStringContainsString('title="Share this court room"', $list);
        $this->assertStringContainsString('href="https://sci-vc.webex.com/meet/court01" target="_blank"', $html);
    }

    public function test_high_court_judges_list_shows_the_bench_judges_meeting_id_and_emails(): void
    {
        CourtBench::create([
            'court_key' => 'delhi-high-court',
            'court_room' => '1',
            'bench' => 'DB-1',
            'judges' => [['name' => 'Justice A', 'designation' => 'Acting Chief Justice'], ['name' => 'Justice B']],
            'vc_meeting_id' => '25161366727',
            'email' => 'one@dhc.nic.in / two.dhc@gov.in',
        ]);

        $this->get(route('courts.delhi-high-court.judges'))
            ->assertOk()
            ->assertSeeInOrder(['Court', '1', 'DB-1', 'Justice A', 'Acting Chief Justice', 'Justice B', 'VC meeting ID', '25161366727'])
            ->assertSee('href="mailto:one@dhc.nic.in"', false)
            ->assertSee('href="mailto:two.dhc@gov.in"', false);
    }

    public function test_each_delhi_district_has_its_own_list_and_unknown_districts_are_not_found(): void
    {
        CourtBench::create(['court_key' => 'delhi-district-west', 'court_room' => 'Room 201', 'judges' => [['name' => 'Sh. Pitamber Dutt']]]);
        CourtBench::create(['court_key' => 'delhi-district-south', 'court_room' => 'Room 9', 'judges' => [['name' => 'Ms. Someone Else']]]);

        $this->get(route('courts.delhi-district-courts.judges', 'west'))
            ->assertOk()
            ->assertSee('West District, Delhi (Tis Hazari)')
            ->assertSee(CourtBench::SOURCES['delhi-district-west'])
            ->assertSee('Sh. Pitamber Dutt')
            ->assertDontSee('Ms. Someone Else');

        $this->get(route('courts.delhi-district-courts.judges', 'atlantis'))->assertNotFound();

        $this->get(route('courts.delhi-district-courts'))
            ->assertOk()
            ->assertSee('href="'.route('courts.delhi-district-courts.judges', 'north-west').'"', false);
    }

    public function test_seeded_lists_cover_every_supreme_court_court_and_the_delhi_high_court_roster(): void
    {
        $this->seed(CourtBenchSeeder::class);

        $this->assertSame(19, CourtBench::where('court_key', 'supreme-court')->count());
        $this->assertSame(
            'https://sci-vc.webex.com/meet/registrarcourt02',
            CourtBench::where('court_key', 'supreme-court')->where('court_room', 'R-02')->value('vc_link'),
        );

        $this->get(route('courts.delhi-high-court.judges'))
            ->assertOk()
            ->assertSee('As per the Roster of Sitting effective 07.10.2026')
            ->assertSeeInOrder(['All', '47', 'Division Bench', '10', 'Single Bench — Civil', '11', 'Single Bench — Criminal', '9', 'Original Side', '6', 'IP Division', '2', 'Joint Registrar', '9'])
            ->assertSeeInOrder(['DB-VII', "Hon'ble Mr. Justice Avneesh Jhingan", "Hon'ble Ms. Justice Shail Jain", 'Service Tax'])
            ->assertSee('href="https://virtualcourtdhc.webex.com/meet/virtualcourtdhc1"', false)
            ->assertDontSee('VC link coming soon')
            ->assertSee('href="https://delhihighcourt.nic.in/web/cause-lists"', false);

        // VC links and court numbers as in the cause list of 23.11.2026.
        $this->assertSame(47, CourtBench::where('court_key', 'delhi-high-court')->whereNotNull('vc_link')->count());
        $this->assertSame("Hon'ble Mr. Justice Anup Jairam Bhambhani", CourtBench::where('court_key', 'delhi-high-court')->where('court_room', '44')->first()->judgeList()[0]['name']);

        // One tap narrows the list to a section.
        $this->get(route('courts.delhi-high-court.judges', ['section' => 'single-bench-criminal']))
            ->assertOk()
            ->assertSee("Hon'ble Mr. Justice Sanjeev Narula")
            ->assertDontSee('DB-VII');
    }

    public function test_an_empty_list_says_it_is_coming(): void
    {
        $this->get(route('courts.delhi-high-court.judges'))
            ->assertOk()
            ->assertSee('This list is being prepared and will appear here soon.');
    }

    public function test_court_lists_are_one_tap_from_the_court_pages(): void
    {
        $this->get(route('courts.supreme-court'))->assertSee('href="'.route('courts.supreme-court.court-list').'"', false);
        $this->get(route('courts.delhi-high-court'))->assertSee('href="'.route('courts.delhi-high-court.judges').'"', false);
    }

    public function test_import_loads_rooms_from_a_csv_and_replace_swaps_a_courts_list(): void
    {
        $file = storage_path('framework/testing/benches.csv');
        File::ensureDirectoryExists(dirname($file));
        File::put($file, implode("\n", [
            'court_key,court_room,bench,judges,designations,vc_link,vc_meeting_id,email,photos',
            'delhi-high-court,1,DB-1,Justice A|Justice B,Acting Chief Justice|,https://dhc.example/1,111,a@dhc.nic.in,',
            'delhi-high-court,2,,Justice C,,not-a-link,,,',
            'mars-court,9,,Nobody,,,,,',
        ]));

        CourtBench::create(['court_key' => 'delhi-high-court', 'court_room' => 'old']);
        CourtBench::create(['court_key' => 'supreme-court', 'court_room' => '1']);

        $this->artisan('courts:import-benches', ['file' => $file, '--replace' => true])
            ->expectsOutputToContain("unknown court_key 'mars-court'")
            ->expectsOutputToContain('VC link is not a web address')
            ->expectsOutput('Imported 2 court rooms.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('court_benches', ['court_room' => 'old']);
        $this->assertDatabaseHas('court_benches', ['court_key' => 'supreme-court', 'court_room' => '1']);

        $first = CourtBench::where('court_room', '1')->where('court_key', 'delhi-high-court')->firstOrFail();
        $this->assertSame([
            ['name' => 'Justice A', 'designation' => 'Acting Chief Justice', 'photo' => null],
            ['name' => 'Justice B', 'designation' => null, 'photo' => null],
        ], $first->judgeList());
        $this->assertNull(CourtBench::where('court_room', '2')->value('vc_link'));

        File::delete($file);
    }

    public function test_import_shortens_values_longer_than_their_column_instead_of_failing(): void
    {
        $file = storage_path('framework/testing/long-benches.csv');
        File::ensureDirectoryExists(dirname($file));
        $longSection = str_repeat('Chief Judicial Magistrate / ', 4);
        File::put($file, "court_key,section,court_room,judges\ndelhi-district-south-west,\"{$longSection}\",8,Ms. Neha Priya\n");

        $this->artisan('courts:import-benches', ['file' => $file])
            ->expectsOutputToContain('section is longer than 60 characters, shortened')
            ->expectsOutput('Imported 1 court rooms.')
            ->assertSuccessful();

        $this->assertSame(mb_substr(trim($longSection), 0, 60), CourtBench::where('court_key', 'delhi-district-south-west')->value('section'));

        File::delete($file);
    }
}
