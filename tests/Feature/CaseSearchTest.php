<?php

namespace Tests\Feature;

use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        CourtBench::create([
            'court_key' => 'delhi-district-south-east',
            'court_room' => '612, main building',
            'judges' => [['name' => 'Sh. Akash Jain', 'designation' => 'District Judge-05']],
            'vc_link' => 'https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08',
        ]);

        // Judge and room known only from the official court list.
        LegalCase::create(['case_title' => 'Listed vs Matter', 'court_name' => 'District Judge - 05', 'court_complex' => 'District and Sessions Judge, South-East, Saket, Delhi', 'case_status' => 'pending']);
        // Judge typed on the case, room on a hearing.
        $own = LegalCase::create(['case_title' => 'Own vs Entries', 'court_name' => 'Civil Judge-02', 'court_complex' => 'Senior Civil Judge cum RC, Jalaun, Uttar Pradesh', 'judge_name' => 'Ms. Kavita Rao', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $own->id, 'hearing_date' => '2026-03-12', 'court_room' => 'Room 7B']);
        LegalCase::create(['case_title' => 'Other vs Unrelated', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
    }

    public function test_case_list_finds_cases_by_judge_room_and_court_including_the_official_list(): void
    {
        $find = fn (string $search, string $type = 'all') => $this->get(route('cases.index', ['search' => $search, 'search_type' => $type]))->assertOk();

        $find('akash')->assertSee('Listed vs Matter')->assertDontSee('Own vs Entries')->assertDontSee('Other vs Unrelated');
        $find('612', 'room')->assertSee('Listed vs Matter')->assertDontSee('Own vs Entries');
        $find('kavita', 'judge')->assertSee('Own vs Entries')->assertDontSee('Listed vs Matter');
        $find('7B', 'room')->assertSee('Own vs Entries')->assertDontSee('Listed vs Matter');
        $find('Jalaun', 'court')->assertSee('Own vs Entries')->assertDontSee('Other vs Unrelated');
        $find('akash', 'case_number')->assertDontSee('Listed vs Matter')->assertSee('No case matches your search.');
    }

    public function test_main_search_finds_cases_and_official_court_rooms_by_judge_or_room(): void
    {
        $this->get(route('search', ['q' => 'Akash']))
            ->assertOk()
            ->assertSee('Listed vs Matter')
            ->assertSee('Court rooms')
            ->assertSee('Sh. Akash Jain')
            ->assertSee('Room 612, main building')
            ->assertSee('href="https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08"', false)
            ->assertSee('href="'.route('courts.delhi-district-courts.judges', 'south-east').'"', false);

        $this->get(route('search', ['q' => 'kavita']))->assertOk()->assertSee('Own vs Entries')->assertDontSee('Listed vs Matter');
    }
}
