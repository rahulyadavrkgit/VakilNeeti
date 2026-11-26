<?php

namespace Tests\Feature;

use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\CourtBenchMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourtBenchMatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $room = fn (string $courtKey, ?string $courtRoom, string $judge, string $designation, string $vcLink) => CourtBench::create([
            'court_key' => $courtKey,
            'court_room' => $courtRoom,
            'judges' => [['name' => $judge, 'designation' => $designation]],
            'vc_link' => $vcLink,
        ]);

        $room('delhi-district-west', null, 'Dr. Sugandha Aggarwal', 'ADJ- 04', 'https://districtcourtdelhi.webex.com/meet/adj04west');
        $room('delhi-district-west', null, 'Sh Harish Kumar', 'DJ (COMMERCIAL)-04', 'https://districtcourtdelhi.webex.com/meet/djcomm04-west');
        $room('delhi-district-west', null, 'Ms. Neha Pandey', 'JSCC/ASCJ/GJ', 'https://districtcourtdelhi.webex.com/meet/ascjwest2');
        $room('delhi-district-south-east', '612, main building', 'Sh. Akash Jain', 'District Judge-05', 'https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08');
        $room('delhi-district-central-rouse-avenue-courts', '503', 'Sh. Ashwani Panwar', 'ACJM-04', 'https://districtcourtdelhi.webex.com/join/readeracmm04radc');
        $room('delhi-district-central-rouse-avenue-courts', '401', 'Ms. Jyoti Maheshwari', 'ACJM-05', 'https://districtcourtdelhi.webex.com/meet/acjm05-racc');
    }

    /**
     * @return array<string, array{string, string, ?string}>
     */
    public static function cases(): array
    {
        return [
            'eCourts capitals and spaces' => ['DISTRICT JUDGE - 04', 'District and Sessions Judge, West, THC, Delhi', 'Dr. Sugandha Aggarwal'],
            'commercial court is a different room' => ['District Judge (Commercial Courts)-04', 'District and Sessions Judge, West, THC, Delhi', 'Sh Harish Kumar'],
            'cum and Addl. spelled out' => ['JSCC-cum-Addl. SCJ-cum-Guardian Judge', 'Senior Civil Judge cum RC, West, THC, Delhi', 'Ms. Neha Pandey'],
            'district from a hyphenated name' => ['District Judge - 05', 'District and Sessions Judge, South-East, Saket, South East, Delhi', 'Sh. Akash Jain'],
            'Rouse Avenue before Central and New Delhi' => ['ADDITIONAL CHIEF JUDICIAL MAGISTRATE-04 MP/MLA CASES', 'Chief Metropolitan Magistrate, Rouse Avenue, New Delhi, Central', 'Sh. Ashwani Panwar'],
            'no number between two ACJM rooms: no guess' => ['Addl. Chief Judicial Magistrate', 'Chief Metropolitan Magistrate, Rouse Avenue, New Delhi, Central', null],
            'wrong number' => ['District Judge - 09', 'District and Sessions Judge, South-East, Saket, Delhi', null],
            'outside Delhi' => ['District & Session Judge', 'District and Sessions Court, Jalaun, Uttar Pradesh', null],
            'High Court' => ['High Court of Delhi', 'Principal Bench at Delhi', null],
        ];
    }

    #[DataProvider('cases')]
    public function test_a_case_court_is_matched_to_its_room_in_the_official_list(string $courtName, string $courtComplex, ?string $expectedJudge): void
    {
        $bench = app(CourtBenchMatcher::class)->find($courtName, $courtComplex);

        $this->assertSame($expectedJudge, $bench?->judgeList()[0]['name']);
    }

    public function test_the_judge_name_finds_the_room_when_the_designation_does_not(): void
    {
        $bench = app(CourtBenchMatcher::class)->find('Court of the ACJM', 'Rouse Avenue, Delhi', 'Ms Jyoti Maheshwari');

        $this->assertSame('401', $bench?->court_room);
    }

    public function test_calendar_and_case_page_fill_judge_room_and_vc_link_from_the_official_list(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create([
            'case_title' => 'Prahlad vs Sanjay',
            'court_name' => 'District Judge - 05',
            'court_complex' => 'District and Sessions Judge, South-East, Saket, South East, Delhi',
            'case_status' => 'pending',
        ]);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => today()->toDateString()]);

        $this->get(route('cases.calendar', ['view' => 'day', 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertSeeInOrder(['District Judge - 05', 'Room 612, main building', 'Sh. Akash Jain', 'Join VC'])
            ->assertSee('title="From the court list"', false)
            ->assertSee('href="https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08"', false);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Sh. Akash Jain')
            ->assertSee('(court list)', false)
            ->assertSee('href="'.route('courts.delhi-district-courts.judges', 'south-east').'"', false);
    }

    public function test_what_the_case_records_itself_beats_the_official_list(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create([
            'case_title' => 'Prahlad vs Sanjay',
            'court_name' => 'District Judge - 05',
            'court_complex' => 'District and Sessions Judge, South-East, Saket, Delhi',
            'judge_name' => 'Ms. Own Entry',
            'court_room' => '9',
            'case_status' => 'pending',
        ]);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => today()->toDateString()]);

        $this->get(route('cases.calendar', ['view' => 'day', 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('Ms. Own Entry')
            ->assertSee('Room 9')
            ->assertDontSee('Sh. Akash Jain')
            ->assertSee('href="https://districtcourtdelhi.webex.com/meet/ddc.vc.southeast08"', false);
    }
}
