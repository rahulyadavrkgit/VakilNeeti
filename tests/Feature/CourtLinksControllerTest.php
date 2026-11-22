<?php

namespace Tests\Feature;

use App\Http\Controllers\CourtLinksController;
use Tests\TestCase;

class CourtLinksControllerTest extends TestCase
{
    public function test_supreme_court_page_links_to_every_official_service_in_a_new_tab(): void
    {
        $response = $this->get(route('court-directory.supreme-court'))
            ->assertOk()
            ->assertSee('Physical display board')
            ->assertSee('E-gate pass');

        foreach (CourtLinksController::SUPREME_COURT as $link) {
            $response->assertSee('href="'.$link['url'].'" target="_blank" rel="noopener noreferrer" data-direct-share', false);
        }

        $response->assertSee('https://cdb.sci.gov.in/', false)
            ->assertSee('https://wdb.sci.gov.in/display_original.php', false);
    }

    public function test_display_boards_page_lists_each_court_and_is_public(): void
    {
        $response = $this->get(route('court-directory.display-boards'))->assertOk();

        foreach (CourtLinksController::DISPLAY_BOARDS as $court) {
            $response->assertSee($court['court']);

            foreach ($court['boards'] as $board) {
                $this->assertStringStartsWith('https://', $board['url']);
                $response->assertSee('href="'.$board['url'].'"', false);
            }
        }
    }
}
