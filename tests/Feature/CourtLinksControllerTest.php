<?php

namespace Tests\Feature;

use App\Http\Controllers\CourtLinksController;
use App\Models\BarAssociation;
use App\Models\BarCouncil;
use App\Models\CourtBench;
use App\Services\DistrictCourtDocuments;
use Database\Seeders\BarCouncilAndAssociationSeeder;
use Database\Seeders\CourtCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourtLinksControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: list<array<string, string>>}>
     */
    public static function linkPages(): array
    {
        return [
            'supreme court' => ['courts.supreme-court', CourtLinksController::SUPREME_COURT],
            'delhi high court' => ['courts.delhi-high-court', CourtLinksController::DELHI_HIGH_COURT],
            'ecourts services' => ['courts.ecourts-services', CourtLinksController::ECOURTS_SERVICES],
        ];
    }

    /**
     * @param  list<array<string, string>>  $links
     */
    #[DataProvider('linkPages')]
    public function test_link_pages_are_public_and_open_official_links_through_the_in_site_viewer(string $route, array $links): void
    {
        $response = $this->get(route($route))->assertOk();

        foreach ($links as $link) {
            $this->assertStringStartsWith('https://', $link['url']);
            $response->assertSee($link['title'])
                ->assertSee('href="'.$link['url'].'" rel="noopener noreferrer"', false)
                ->assertDontSee('href="'.$link['url'].'" target="_blank"', false);
        }

        $response->assertSee('<meta name="description"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_supreme_court_page_has_both_display_boards(): void
    {
        $this->get(route('courts.supreme-court'))
            ->assertOk()
            ->assertSee('https://cdb.sci.gov.in/', false)
            ->assertSee('https://wdb.sci.gov.in/display_original.php', false);
    }

    public function test_each_delhi_district_has_its_services_and_rosters_open_inside_vakilniti(): void
    {
        $response = $this->get(route('courts.delhi-district-courts'))->assertOk();

        foreach (CourtLinksController::DELHI_DISTRICT_COMPLEXES as $complex => $districts) {
            $response->assertSee($complex)->assertSee('data-complex-panel="'.Str::slug($complex).'"', false);

            foreach ($districts as $district) {
                $slug = Str::slug($district['district']);
                $response->assertSee($district['district'])
                    ->assertSee(route('courts.delhi-district-courts.judges', $slug), false)
                    ->assertSee('href="'.$district['url'].'judges-on-leave/"', false)
                    ->assertSee(route('courts.delhi-district-courts.document', [$slug, 'bail-roster']), false)
                    ->assertSee(route('courts.delhi-district-courts.document', [$slug, 'duty-magistrate']), false)
                    ->assertSee('href="'.$district['url'].'case-status-search-by-case-number/"', false)
                    ->assertSee('href="'.$district['url'].'cause-list-%e2%81%84-daily-board/"', false);
            }
        }

        // One complex at a time: the first, unless another is asked for (as when coming back from a roster).
        $response->assertSee('data-complex-pick="rohini" aria-pressed="true"', false)
            ->assertSeeInOrder(['Judges, rooms &amp; VC links', 'On leave', 'Bail roster', 'Duty magistrate', 'Case status', 'Cause list', 'e-Filing'], false);
        $this->get(route('courts.delhi-district-courts', ['complex' => 'saket']))->assertOk()
            ->assertSee('data-complex-pick="saket" aria-pressed="true"', false);

        // The court's own roster pages stay one tap away from each roster ("Older rosters on the court's site").
        $this->assertSame('https://rohini.dcourts.gov.in/document-category/bail-roster-link-roster/', DistrictCourtDocuments::listPage(DistrictCourtDocuments::districts()['north-west'], 'bail-roster'));
        $this->assertSame('https://westdelhi.dcourts.gov.in/?s=bail+roster', DistrictCourtDocuments::listPage(DistrictCourtDocuments::districts()['west'], 'bail-roster'));
    }

    public function test_district_page_counts_courts_and_finds_a_judge_or_room(): void
    {
        CourtBench::create(['court_key' => 'delhi-district-west', 'court_room' => '201', 'judges' => [['name' => 'Sh. Pitamber Dutt', 'designation' => 'Principal District & Sessions Judge']], 'vc_link' => 'https://vc.example/west1']);
        CourtBench::create(['court_key' => 'delhi-district-west', 'judges' => [['name' => 'Vacant', 'designation' => 'ADJ-03']]]);
        CourtBench::create(['court_key' => 'delhi-district-south', 'court_room' => '031', 'judges' => [['name' => 'Ms. Shreya Arora Mehta', 'designation' => 'District Judge-04']]]);

        $this->get(route('courts.delhi-district-courts'))
            ->assertOk()
            ->assertSee('3 courts in 11 districts')
            ->assertSeeInOrder(['>West</h2>', '>2</strong> courts', '>1</strong> vacant'], false);

        $this->get(route('courts.delhi-district-courts', ['q' => 'dutt']))
            ->assertOk()
            ->assertSee('1 court for "dutt"', false)
            ->assertSee('Principal District &amp; Sessions Judge · Room 201 · West', false)
            ->assertSee('href="https://vc.example/west1"', false)
            ->assertDontSee('Shreya Arora Mehta');

        $this->get(route('courts.delhi-district-courts', ['q' => '031']))->assertSee('Ms. Shreya Arora Mehta');
    }

    public function test_high_courts_page_lists_all_25_with_their_websites(): void
    {
        $this->assertCount(25, CourtLinksController::HIGH_COURTS);

        $response = $this->get(route('courts.high-courts'))
            ->assertOk()
            ->assertSee('href="https://hcservices.ecourts.gov.in/"', false)
            ->assertSee('href="'.route('courts.delhi-high-court').'"', false);

        foreach (CourtLinksController::HIGH_COURTS as $court) {
            $this->assertStringStartsWith('https://', $court['url']);
            $response->assertSee($court['name']);

            if ($court['seat'] !== 'New Delhi') {
                $response->assertSee('href="'.$court['url'].'"', false);
            }
        }

        $this->get(route('courts.index'))->assertSee(route('courts.high-courts'), false);
    }

    public function test_bar_directory_lists_the_bar_council_of_india_and_every_state_bar_council(): void
    {
        $this->seed(CourtCatalogueSeeder::class);
        $this->seed(BarCouncilAndAssociationSeeder::class);

        $this->assertSame(25, BarCouncil::count());
        $this->assertSame('https://www.bctnpy.org/', BarCouncil::where('council_name', 'Bar Council of Tamil Nadu & Puducherry')->value('official_url'));
        $this->assertNull(BarAssociation::where('association_name', 'New Delhi Bar Association (NDBA)')->value('official_url'));

        $this->get(route('bar-directory.index'))
            ->assertOk()
            ->assertSee('Bar Council of India')
            ->assertSee('Bar Council of Delhi')
            ->assertSee('Bar Council of Kerala');
    }

    public function test_tribunals_page_lists_every_tribunal_with_its_official_website(): void
    {
        $response = $this->get(route('tribunals.index'))->assertOk();

        foreach (CourtLinksController::TRIBUNALS as $group => $tribunals) {
            $response->assertSee($group);

            foreach ($tribunals as $tribunal) {
                $this->assertStringStartsWith('https://', $tribunal['url']);
                $response->assertSee($tribunal['short'])->assertSee('href="'.$tribunal['url'].'"', false);
            }
        }
    }

    public function test_display_boards_and_courts_hub_are_public(): void
    {
        // Each court has a single choice in the viewer, alongside its original board links.
        $response = $this->get(route('courts.display-boards'))
            ->assertOk()
            ->assertSee('data-board-page', false)
            ->assertSee('data-board-select', false);

        foreach (CourtLinksController::DISPLAY_BOARDS as $court) {
            $response->assertSee('<option value="'.route('courts.live-board', $court['live']).'"', false)
                ->assertSee($court['court']);

            foreach ($court['boards'] as $board) {
                $response->assertSee('href="'.$board['url'].'"', false)
                    ->assertSee($board['title']);
            }
        }

        $this->get(route('courts.index'))
            ->assertOk()
            ->assertSee(route('courts.supreme-court'), false)
            ->assertSee(route('courts.delhi-district-courts'), false);
    }
}
