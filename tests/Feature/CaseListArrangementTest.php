<?php

namespace Tests\Feature;

use App\Models\CauseListAppearance;
use App\Models\CourtDocument;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\CauseLists\CauseListRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseListArrangementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
        $this->actingAs(User::factory()->create());
    }

    public function test_active_cases_run_by_next_date_then_the_undated_by_last_update(): void
    {
        $this->case('Later Date', next: '2026-03-20');
        $this->case('Sooner Date', next: '2026-03-12');
        $this->case('Stale Date', next: '2026-02-01', updated: '2026-03-05');
        $this->case('No Date', updated: '2026-03-01');
        $this->case('Closed Matter', status: 'disposed', disposedOn: '2026-01-15');

        $this->get(route('cases.index'))->assertOk()
            ->assertSeeInOrder(['Thu 12 Mar 2026', 'Sooner Date', 'Fri 20 Mar 2026', 'Later Date', 'No date ahead · by last update', 'No Date', 'Stale Date'])
            ->assertDontSee('Closed Matter')
            ->assertSee('Disposed');
    }

    public function test_disposed_cases_have_their_own_list_by_date_of_disposal(): void
    {
        $this->case('Disposed Later', status: 'disposed', disposedOn: '2025-09-01');
        $this->case('Disposed First', status: 'withdrawn', disposedOn: '2024-02-10');
        $this->case('Disposed Undated', status: 'closed');
        $this->case('Still Running', next: '2026-03-12');

        $this->get(route('cases.index', ['list' => 'disposed']))->assertOk()
            ->assertSeeInOrder(['Disposed in February 2024', 'Disposed First', 'Disposed in September 2025', 'Disposed Later', 'Date of disposal not recorded', 'Disposed Undated'])
            ->assertDontSee('Still Running');
    }

    public function test_by_court_groups_districts_and_high_court_benches_each_by_date(): void
    {
        $this->case('Jalaun Late', next: '2026-03-25', court: ['court_level' => 'district', 'state_code' => 'UP', 'district' => 'Jalaun', 'court_code' => 'UPJL01']);
        $this->case('Jalaun Early', next: '2026-03-11', court: ['court_level' => 'district', 'state_code' => 'UP', 'district' => 'Jalaun', 'court_code' => 'UPJL01']);
        $this->case('Apex Matter', next: '2026-03-30', court: ['court_level' => 'supreme', 'court_code' => 'SC']);
        $listed = $this->case('Bench Matter', court: ['court_level' => 'high', 'state_code' => 'UP', 'court_code' => 'UPHC01']);
        app(CauseListRecorder::class)->ensureSource('allahabad-high-court', 'allahabad-high-court', 'https://example.test');
        $list = CourtDocument::create(['provider_key' => 'allahabad-high-court', 'document_key' => 'c90', 'listing_date' => '2026-03-11', 'document_label' => 'COURT NO.90', 'source_url' => 'https://example.test/90.pdf']);
        CauseListAppearance::create(['court_document_id' => $list->id, 'legal_case_id' => $listed->id, 'record_key' => 'r', 'item_number' => '97', 'court_room' => '90', 'judge_name' => 'JUSTICE VANI RANJAN AGRAWAL', 'match_status' => 'matched']);

        $this->get(route('cases.index', ['arrange' => 'court']))->assertOk()
            ->assertSeeInOrder(['Supreme Court', 'Apex Matter', 'Allahabad HC · Court 90 · Justice Vani Ranjan Agrawal', 'Bench Matter', 'Jalaun, UP', 'Jalaun Early', 'Jalaun Late']);
    }

    public function test_each_case_card_has_icon_actions_and_downloads_as_a_pdf(): void
    {
        $case = $this->case('Sharma vs State', next: '2026-03-12');
        $case->update(['case_number' => 'HMA/1/2026']);

        $this->get(route('cases.index'))->assertOk()
            ->assertSee('aria-label="Download case as PDF"', false)->assertSee('aria-label="Share case details"', false)
            ->assertSee('aria-label="Tags"', false)->assertSee('aria-label="Delete case"', false);

        $download = $this->get(route('cases.download', $case))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')->assertDownload('hma-1-2026.pdf');
        $this->assertStringStartsWith('%PDF-', $download->getContent());

        $this->actingAs(User::factory()->create())->get(route('cases.download', $case))->assertNotFound();
    }

    public function test_calendar_is_a_tab_of_the_cases_section(): void
    {
        $bar = str($this->get(route('cases.calendar'))->assertOk()->getContent())->after('class="vn-section-nav')->before('</nav>');

        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*Calendar/', (string) $bar);
        $this->assertStringContainsString('My cases', (string) $bar);
    }

    /**
     * @param  array<string, string>  $court
     */
    private function case(string $title, ?string $next = null, string $status = 'pending', ?string $disposedOn = null, ?string $updated = null, array $court = []): LegalCase
    {
        $case = LegalCase::create(['case_title' => $title, 'court_name' => 'Test Court', 'case_status' => $status, 'disposed_on' => $disposedOn] + $court);

        if ($next) {
            CourtHearing::create(['case_id' => $case->id, 'next_hearing_date' => $next]);
        }

        if ($updated) {
            $case->timestamps = false;
            $case->forceFill(['updated_at' => $updated])->save();
        }

        return $case;
    }
}
