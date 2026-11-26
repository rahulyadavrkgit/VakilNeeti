<?php

namespace Tests\Feature;

use App\Models\CauseListAppearance;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\AdminCaseReview;
use App\Services\DelhiHighCourtCauseList;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DelhiHighCourtCauseListTest extends TestCase
{
    use RefreshDatabase;

    /** A shortened copy of the text the court's PDF gives (layout as extracted). */
    private const LIST_TEXT = <<<'TEXT'
25.11.2026                                           R -1                                 REGULAR MATTER
COURT NO.  01
DIVISION BENCH- I
HON'BLE THE ACTING CHIEF JUSTICE
HON'BLE MR. JUSTICE SANJAY SHARMA
CLICK HERE TO JOIN VC
https://dhcvirtualcourt.webex.com/meet/dhcvirtualcourt9
REGULAR MATTERS
1.  FAO (COMM) 36/2022             HEMANT SINGH                 JAI GUPTA
    CM APPL. 11304/2022            Vs. GRANT THORNTON  INDIA
3.  W.P.(C) 9569/2007              RASHITRYA MUKTI MORCHA       VIJAY CHOUDHARY
    WITH W.P.(C) 298/2008          Vs. UOI & ORS.
COURT NO.33
DIVISION BENCH- IV
            HON'BLE MS.JUSTICE PRATHIBA M. SINGH
CLICK HERE FOR VC
https://dhcvirtualcourt.webex.com/meet/dhcvirtu alcourt6
60 W.P.(C)-17319/2026
61 BAIL APPLN.-5003/2026 MR. RAVI
TEXT;

    public function test_cause_list_text_gives_court_item_bench_and_vc_link(): void
    {
        $entries = collect((new DelhiHighCourtCauseList)->entries(self::LIST_TEXT))->keyBy('key');

        $this->assertSame(['FAOCOMM-36-2022', 'WPC-9569-2007', 'WPC-298-2008', 'WPC-17319-2026', 'BAILAPPLN-5003-2026'], $entries->keys()->all());
        $this->assertSame(['1', '3'], [$entries['FAOCOMM-36-2022']['court'], $entries['WPC-298-2008']['item']]);
        $this->assertSame(['THE ACTING CHIEF JUSTICE', 'MR. JUSTICE SANJAY SHARMA'], $entries['WPC-9569-2007']['judges']);
        $this->assertSame('33', $entries['WPC-17319-2026']['court']);
        $this->assertSame('60', $entries['WPC-17319-2026']['item']);
        $this->assertSame('https://dhcvirtualcourt.webex.com/meet/dhcvirtualcourt6', $entries['WPC-17319-2026']['vc_link']);

        $this->assertSame('WPC-7570-2023', DelhiHighCourtCauseList::keyForCase('W.P.(C)/7570/2023'));
        $this->assertSame('CRLMC-6584-2022', DelhiHighCourtCauseList::keyForCase('CRL.M.C./6584/2022'));
        $this->assertSame('WPC-7570-2023', DelhiHighCourtCauseList::keyForCase('7570/2023', 'W.P.(C)'));
        $this->assertNull(DelhiHighCourtCauseList::keyForCase('7570/2023'));
    }

    public function test_only_lists_for_the_date_are_read_and_deletion_notes_are_skipped(): void
    {
        $documents = (new DelhiHighCourtCauseList)->documentsInPage($this->listingPage(), Carbon::parse('2026-11-25'));

        $this->assertSame([
            ['title' => 'Cause List of Sitting of Benches for 25.11.2026', 'url' => 'https://delhihighcourt.nic.in/files/2026-11/cause-list/c_25112026.pdf'],
        ], $documents);
    }

    public function test_listed_cases_show_on_home_and_the_case_page_with_one_bell_note(): void
    {
        $this->travelTo('2026-11-24 20:30:00');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner);
        $listed = LegalCase::create(['case_title' => 'Rashtriya Mukti Morcha vs UOI', 'case_number' => 'W.P.(C)/298/2008', 'case_type' => 'W.P.(C)', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        $notListed = LegalCase::create(['case_title' => 'Asha vs State', 'case_number' => 'W.P.(C)/1/2020', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        $elsewhere = LegalCase::create(['case_title' => 'Saket matter', 'case_number' => 'FAO(COMM)/36/2022', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        Http::fake([
            'delhihighcourt.nic.in/web/cause-lists/*' => Http::response($this->listingPage()),
            'delhihighcourt.nic.in/files/*' => Http::response('%PDF-1.6 test'),
        ]);
        $this->partialMock(DelhiHighCourtCauseList::class, fn ($mock) => $mock->shouldReceive('textOf')->andReturn(self::LIST_TEXT));

        $this->artisan('courts:delhi-hc-cause-list')->expectsOutputToContain('Found 2 listings')->assertSuccessful();
        $this->artisan('courts:delhi-hc-cause-list')->assertSuccessful();

        // Listed tomorrow in the day's list, and the day after in the advance list.
        $appearances = CauseListAppearance::query()->with('courtDocument')->get();
        $this->assertSame(['2026-11-25', '2026-11-26'], $appearances->map(fn ($appearance) => $appearance->courtDocument->listing_date->toDateString())->sort()->values()->all());
        $this->assertSame([[$listed->id, '1', '3']], $appearances->map(fn ($appearance) => [$appearance->legal_case_id, $appearance->court_room, $appearance->item_number])->unique()->values()->all());
        $this->assertSame(0, CauseListAppearance::query()->whereIn('legal_case_id', [$notListed->id, $elsewhere->id])->count());

        $this->assertSame(['Listed Thu, 26 Nov · Delhi HC · Court 1 · Item 3', 'Listed tomorrow · Delhi HC · Court 1 · Item 3'], $owner->notifications()->get()->pluck('data.title')->sort()->values()->all());

        $this->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder(['Next hearing · Tomorrow 25th Nov', 'Rashtriya Mukti Morcha vs UOI', 'W.P.(C)/298/2008', 'ITEM 3', 'Court Room 1'])
            ->assertDontSee('Also in the cause lists')
            ->assertSee('https://dhcvirtualcourt.webex.com/meet/dhcvirtualcourt9');
        $this->get(route('cases.show', $listed))->assertOk()->assertSee('id="cause-list"', false)->assertSee('c_25112026.pdf');
        $this->get(route('cases.show', $notListed))->assertOk()->assertDontSee('id="cause-list"', false);

        $diary = $this->get(route('cases.index', ['hearing_filter' => 'tomorrow']))->assertOk();
        $this->assertSame([$listed->id], $diary->viewData('cases')->pluck('id')->all());
        $this->assertStringContainsString('Rashtriya Mukti Morcha', $diary->viewData('shareText'));

        // My cases shows it for tomorrow, with the cause list details.
        $this->get(route('cases.index', ['hearing_filter' => 'tomorrow']))->assertOk()->assertSeeInOrder(['Tomorrow', 'Rashtriya Mukti Morcha vs UOI', 'Join VC', 'My item', '3', 'c_25112026.pdf']);

        // The case had no date ahead, so the first listing became its next date, once, with the room and item.
        $saved = CourtHearing::query()->where('case_id', $listed->id)->get();
        $this->assertSame([['2026-11-25', '1', '3', 'cause_list', $owner->id]], $saved->map(fn ($hearing) => [$hearing->hearing_date->toDateString(), $hearing->court_room, $hearing->item_number, $hearing->source_type, $hearing->user_id])->all());
        $this->assertSame("From the court's cause list", $saved->first()->dateSourceLabel());
        $this->assertSame(0, CourtHearing::query()->whereIn('case_id', [$notListed->id, $elsewhere->id])->count());

        $this->actingAs($other)->get(route('dashboard'))->assertOk()->assertDontSee('cause lists');

        // When the advocate already has a different date, the list adds nothing and the two dates are flagged.
        $this->actingAs($owner);
        CourtHearing::query()->where('source_type', 'cause_list')->delete();
        $hearing = CourtHearing::create(['case_id' => $listed->id, 'next_hearing_date' => '2026-11-27']);
        $this->artisan('courts:delhi-hc-cause-list')->assertSuccessful();
        $this->assertSame(0, CourtHearing::query()->where('source_type', 'cause_list')->count());
        $review = app(AdminCaseReview::class);
        $this->assertSame([$listed->id], $review->pending('listing-conflict')->pluck('legal_cases.id')->all());
        $this->get(route('cases.show', $listed))->assertOk()
            ->assertSee('These dates differ')->assertSee('27 Nov 2026')->assertSee('25 Nov 2026');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->withSession(['admin_verified' => [
            'id' => $admin->id, 'version' => 0, 'expires' => now()->addHour()->timestamp,
        ]]);
        $this->post(route('admin.review.notify', $listed))->assertRedirect();
        $this->post(route('admin.review.notify', $listed))->assertRedirect();
        $this->assertSame(1, $owner->notifications()->where('data->title', 'Check conflicting hearing dates')->count());

        $this->actingAs($owner);
        $hearing->update(['next_hearing_date' => '2026-11-25']);
        $this->assertSame(0, $review->pending('listing-conflict')->count());
    }

    public function test_a_listing_never_replaces_a_date_the_advocate_already_has(): void
    {
        $this->travelTo('2026-11-24 20:30:00');
        $this->actingAs(User::factory()->create());
        $withDate = LegalCase::create(['case_title' => 'Rashtriya Mukti Morcha vs UOI', 'case_number' => 'W.P.(C)/298/2008', 'case_type' => 'W.P.(C)', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $withDate->id, 'next_hearing_date' => '2026-12-10']);
        $pastOnly = LegalCase::create(['case_title' => 'Hemant Singh vs Grant Thornton', 'case_number' => 'FAO(COMM)/36/2022', 'case_type' => 'FAO(COMM)', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $pastOnly->id, 'hearing_date' => '2026-10-01']);

        Http::fake([
            'delhihighcourt.nic.in/web/cause-lists/*' => Http::response($this->listingPage()),
            'delhihighcourt.nic.in/files/*' => Http::response('%PDF-1.6 test'),
        ]);
        $this->partialMock(DelhiHighCourtCauseList::class, fn ($mock) => $mock->shouldReceive('textOf')->andReturn(self::LIST_TEXT));
        $this->artisan('courts:delhi-hc-cause-list')->assertSuccessful();

        // A date ahead stays as it is; a case whose dates have all passed gets the listing.
        $this->assertSame(0, CourtHearing::query()->where('case_id', $withDate->id)->where('source_type', 'cause_list')->count());
        $this->assertSame(['2026-11-25'], CourtHearing::query()->where('case_id', $pastOnly->id)->where('source_type', 'cause_list')->get()->map(fn ($hearing) => $hearing->hearing_date->toDateString())->all());
    }

    public function test_the_check_runs_morning_and_evening(): void
    {
        $expression = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'courts:delhi-hc-cause-list'))?->expression;

        $this->assertSame('15 7,9,20,22 * * *', $expression);
    }

    private function listingPage(): string
    {
        $row = fn (string $title, string $date, string $file): string => '<tr><td headers="view-title-table-column" class="views-field views-field-title">'.$title.'   </td>'
            .'<td headers="view-field-date-table-column" class="views-field views-field-field-date">'.$date.'   </td>'
            .'<td headers="x" class="views-field views-field-field-cause-list-file"><p><a href="/files/2026-11/cause-list/'.$file.'" target="_blank">Download</a></p></td></tr>';

        return '<table>'
            .$row('ADVANCE CAUSE LIST OF CASES FOR 26.11.2026', '26-11-2026', 'combined_advance_26112026.pdf')
            .$row('Corrigendum/Deletion Note for 25.11.2026', '25-11-2026', 'deletion_note_25.11.2026.pdf')
            .$row('Cause List of Sitting of Benches for 25.11.2026', '25-11-2026', 'c_25112026.pdf')
            .$row('Cause List of Sitting of Benches for 23.11.2026', '23-11-2026', 'c_23112026.pdf')
            .'</table>';
    }
}
