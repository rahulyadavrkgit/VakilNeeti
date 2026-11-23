<?php

namespace Tests\Feature;

use App\Http\Controllers\CourtLinksController;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourtLinksControllerTest extends TestCase
{
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

    public function test_delhi_district_courts_lists_every_complex_and_district_website(): void
    {
        $response = $this->get(route('courts.delhi-district-courts'))->assertOk()->assertSee('E-Courts filing portal');

        foreach (CourtLinksController::DELHI_DISTRICT_COMPLEXES as $complex => $districts) {
            $response->assertSee($complex);

            foreach ($districts as $district) {
                $response->assertSee($district['district'])
                    ->assertSee('href="'.$district['url'].'"', false)
                    ->assertSee('href="'.$district['vc_links'].'"', false);
            }
        }
    }

    public function test_each_delhi_district_links_to_its_own_case_status_searches(): void
    {
        $this->get(route('courts.delhi-district-courts'))
            ->assertOk()
            ->assertSee('href="https://westdelhi.dcourts.gov.in/case-status-search-by-case-number/"', false)
            ->assertSee('href="https://rohini.dcourts.gov.in/case-status-search-by-advocate/"', false)
            ->assertSee('href="https://southdelhi.dcourts.gov.in/court-orders-search-by-order-date/"', false)
            ->assertSee('href="https://shahdara.dcourts.gov.in/judges-on-leave/"', false);
    }

    public function test_display_boards_and_courts_hub_are_public(): void
    {
        $response = $this->get(route('courts.display-boards'))->assertOk();

        foreach (CourtLinksController::DISPLAY_BOARDS as $court) {
            $response->assertSee($court['court']);

            foreach ($court['boards'] as $board) {
                $response->assertSee('href="'.$board['url'].'"', false);
            }
        }

        $this->get(route('courts.index'))
            ->assertOk()
            ->assertSee(route('courts.supreme-court'), false)
            ->assertSee(route('courts.delhi-district-courts'), false);
    }
}
