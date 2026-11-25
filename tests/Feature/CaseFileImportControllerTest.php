<?php

namespace Tests\Feature;

use App\Http\Controllers\CaseFileImportController;
use App\Models\CourtHearing;
use App\Models\CourtRecordImport;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Unit\Services\EcourtsAppExportParserTest;

class CaseFileImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-22 10:00:00');
    }

    public function test_ecourts_export_preview_then_import_adds_cases_with_their_next_hearing(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('cases.find.ecourts-app.preview'), ['files' => [$this->exportUpload([
            EcourtsAppExportParserTest::districtRecord(),
            EcourtsAppExportParserTest::districtRecord(['cino' => 'DLCT120000552022', 'petparty_name' => 'STATE', 'resparty_name' => 'MOHAN', 'disp_name' => 'DISPOSED', 'date_of_decision' => '2024-01-04', 'date_last_list' => '2024-01-04']),
        ])]])->assertOk()->assertSee('ASHA DEVI vs RAVI KUMAR')->assertSee('12 Jan 2027');

        $this->post(route('cases.find.ecourts-app.import'), [
            'token' => $response->viewData('token'),
            'selected' => ['DLWT010000332007', 'DLCT120000552022'],
            'update_existing' => '1',
        ])->assertRedirect(route('cases.index'))->assertSessionHas('success', '2 cases added.');

        $pending = LegalCase::where('cnr_number', 'DLWT010000332007')->firstOrFail();
        $this->assertSame('Civ DJ/608651/2016', $pending->case_number);
        $this->assertSame('DISTRICT JUDGE - 04', $pending->court_name);
        $this->assertSame('ecourts_app_export', $pending->source_type);
        $hearing = $pending->hearings()->sole();
        $this->assertSame('2026-10-06', $hearing->hearing_date->toDateString());
        $this->assertSame('2027-01-12', $hearing->next_hearing_date->toDateString());

        $disposed = LegalCase::where('cnr_number', 'DLCT120000552022')->firstOrFail();
        $this->assertSame('disposed', $disposed->case_status);
        $this->assertSame('2024-01-04', $disposed->disposed_on->toDateString());
        $this->assertNull($disposed->hearings()->sole()->next_hearing_date);
    }

    public function test_uploading_a_newer_export_updates_dates_without_duplicating_cases(): void
    {
        $this->actingAs(User::factory()->create());
        $this->importRecords([EcourtsAppExportParserTest::districtRecord()]);

        $this->importRecords([EcourtsAppExportParserTest::districtRecord(['date_last_list' => '2027-01-12', 'date_next_list' => '2027-03-01', 'purpose_name' => 'Final Arguments'])])
            ->assertSessionHas('success', '0 cases added, 1 case updated with the latest dates.');

        $case = LegalCase::sole();
        $this->assertSame('Final Arguments', $case->stage);
        $this->assertSame(2, $case->hearings()->count());
        $this->assertSame('2027-03-01', $case->hearings()->latest('hearing_date')->first()->next_hearing_date->toDateString());
    }

    public function test_existing_cases_are_left_alone_when_update_is_off(): void
    {
        $this->actingAs(User::factory()->create());
        $this->importRecords([EcourtsAppExportParserTest::districtRecord()]);

        $this->importRecords([EcourtsAppExportParserTest::districtRecord(['purpose_name' => 'Changed'])], updateExisting: false)
            ->assertSessionHas('success', '0 cases added, 1 already in your cases were left as they were.');

        $this->assertSame('Misc. Arguments', LegalCase::sole()->stage);
    }

    public function test_an_import_token_cannot_be_used_by_another_user(): void
    {
        $this->actingAs(User::factory()->create());
        $token = $this->post(route('cases.find.ecourts-app.preview'), ['files' => [$this->exportUpload([EcourtsAppExportParserTest::districtRecord()])]])->viewData('token');

        $this->actingAs(User::factory()->create());
        $this->post(route('cases.find.ecourts-app.import'), ['token' => $token, 'selected' => ['DLWT010000332007']])
            ->assertRedirect(route('cases.find', ['method' => 'app']));

        $this->assertSame(0, LegalCase::withoutGlobalScopes()->count());
    }

    public function test_a_wrong_file_shows_a_clear_message(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('cases.find'))
            ->post(route('cases.find.ecourts-app.preview'), ['files' => [UploadedFile::fake()->createWithContent('notes.txt', 'just some notes')]])
            ->assertRedirect(route('cases.find'))
            ->assertSessionHasErrors('files');
        $failed = CourtRecordImport::query()->sole();
        $this->assertSame('failed', $failed->import_status);
        $this->get(route('cases.import.history'))->assertOk()->assertSee('Correct and retry');

        $token = $this->post(route('cases.find.ecourts-app.preview'), [
            'retry' => $failed->id,
            'files' => [$this->exportUpload([EcourtsAppExportParserTest::districtRecord()])],
        ])->assertOk()->viewData('token');
        $payload = ['token' => $token, 'selected' => ['DLWT010000332007']];
        $this->post(route('cases.find.ecourts-app.import'), $payload)->assertRedirect(route('cases.index'));
        $this->post(route('cases.find.ecourts-app.import'), $payload)->assertRedirect();
        $this->assertSame(1, LegalCase::query()->count());
        $this->assertSame(['added' => 1, 'updated' => 0, 'skipped' => 0], CourtRecordImport::query()->latest('id')->firstOrFail()->parsed_data['counts']);
        $this->assertDatabaseHas('admin_audit_entries', ['action' => 'import retry imported']);
        $attempt = CourtRecordImport::query()->latest('id')->firstOrFail();
        $this->get(route('cases.import.results', $attempt))->assertOk()->assertSee('Added')->assertSee('608651/2016');
        $this->actingAs(User::factory()->create())->get(route('cases.import.results', $attempt))->assertNotFound();
    }

    public function test_calendar_events_are_matched_to_cases_and_imported_as_hearings(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Asha vs Ravi', 'case_number' => 'CS/12/2024', 'court_name' => 'Tis Hazari', 'case_status' => 'pending']);

        $response = $this->post(route('cases.find.calendar.preview'), [
            'calendar' => UploadedFile::fake()->createWithContent('work.ics', $this->ics([
                ['20261203T043000Z', 'Hearing CS 12/2024', 'Tis Hazari'],
                ['20261204T050000Z', 'Sharma vs State bail', 'Saket Court'],
                ['20261210T050000Z', 'Sharma vs State bail', 'Saket Court'],
                ['20261205T050000Z', 'Birthday dinner', null],
            ])),
            'from' => '2026-11-01',
            'to' => '2027-01-31',
            'likely_only' => '1',
        ])->assertOk()->assertDontSee('Birthday dinner');

        $events = $response->viewData('events');
        $this->assertSame(['case:'.$case->id, 'new', 'new'], $events->pluck('default_action')->all());

        $this->post(route('cases.find.calendar.import'), [
            'token' => $response->viewData('token'),
            'actions' => $events->pluck('default_action')->all(),
        ])->assertRedirect(route('cases.calendar', ['view' => 'list']))
            ->assertSessionHas('success', '3 hearings added, with 1 new case.');

        $this->assertSame('10:00', substr((string) $case->hearings()->sole()->hearing_time, 0, 5));
        $newCase = LegalCase::where('case_title', 'Sharma vs State bail')->sole();
        $this->assertSame('Saket Court', $newCase->court_name);
        $this->assertSame(2, $newCase->hearings()->count());
        $this->assertTrue(CourtHearing::first()->isManual());
    }

    public function test_calendar_import_cannot_attach_hearings_to_another_users_case(): void
    {
        $this->actingAs(User::factory()->create());
        $otherCase = LegalCase::create(['case_title' => 'Theirs', 'court_name' => 'X', 'case_status' => 'pending']);

        $this->actingAs(User::factory()->create());
        $token = $this->post(route('cases.find.calendar.preview'), [
            'calendar' => UploadedFile::fake()->createWithContent('work.ics', $this->ics([['20261203T043000Z', 'Court hearing', null]])),
            'from' => '2026-11-01', 'to' => '2027-01-31',
        ])->viewData('token');

        $this->post(route('cases.find.calendar.import'), ['token' => $token, 'actions' => ['case:'.$otherCase->id]])
            ->assertSessionHas('success', '0 hearings added. 1 could not be saved. Check import history to retry.');

        $this->assertSame(0, CourtHearing::withoutGlobalScopes()->count());
    }

    public function test_a_file_bigger_than_the_server_accepts_returns_a_clear_message(): void
    {
        $this->actingAs(User::factory()->create())
            ->withServerVariables(['CONTENT_LENGTH' => (string) (2 * 1024 ** 3)])
            ->from(route('cases.find', ['method' => 'calendar']))
            ->post(route('cases.find.calendar.preview'))
            ->assertRedirect(route('cases.find', ['method' => 'calendar', 'upload_too_large' => 1]));

        $this->withServerVariables([])
            ->get(route('cases.find', ['method' => 'calendar', 'upload_too_large' => 1]))
            ->assertOk()
            ->assertSee('That file is larger than this server accepts');
    }

    public function test_upload_limit_is_the_smallest_of_php_and_our_own_cap(): void
    {
        $limit = CaseFileImportController::uploadLimitMegabytes(10);

        $this->assertGreaterThanOrEqual(1, $limit);
        $this->assertLessThanOrEqual(10, $limit);
        $this->assertSame(1, CaseFileImportController::uploadLimitMegabytes(1));
    }

    public function test_find_page_offers_all_three_ways(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('cases.find', ['method' => 'calendar']))
            ->assertOk()
            ->assertSee('eCourts app file')
            ->assertSee('Google Calendar')
            ->assertSee('data-switch-panel="method:calendar"', false);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    private function exportUpload(array $records): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('myCases.txt', EcourtsAppExportParserTest::exportFile($records));
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    private function importRecords(array $records, bool $updateExisting = true): TestResponse
    {
        $token = $this->post(route('cases.find.ecourts-app.preview'), ['files' => [$this->exportUpload($records)]])->viewData('token');

        return $this->post(route('cases.find.ecourts-app.import'), [
            'token' => $token,
            'selected' => array_column($records, 'cino'),
            'update_existing' => $updateExisting ? '1' : '0',
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: ?string}>  $events
     */
    private function ics(array $events): string
    {
        $lines = ['BEGIN:VCALENDAR'];

        foreach ($events as $index => [$start, $title, $location]) {
            array_push($lines, 'BEGIN:VEVENT', "UID:event{$index}@google.com", "DTSTART:{$start}", "SUMMARY:{$title}");

            if ($location) {
                $lines[] = "LOCATION:{$location}";
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }
}
