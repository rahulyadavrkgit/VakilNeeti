<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CourtBoardEmbedCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DisplayBoardPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_display_board_picker_to_guests_and_advocates(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Live display boards')
            ->assertSee('https://cdb.sci.gov.in/', false)
            ->assertSee('data-board-manage-toggle', false)
            ->assertSee('data-board-hide', false)
            ->assertSee('data-board-fullscreen', false)
            ->assertSee('data-board-open="full"', false)
            ->assertSee('data-view-toggle="home-modules"', false)
            ->assertSee('for general information and educational purposes only and is not legal advice');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Live display boards', 'Tasks &amp; appointments'], false);
    }

    public function test_boards_are_embedded_only_when_the_court_allows_framing(): void
    {
        $check = new CourtBoardEmbedCheck;

        $this->assertFalse($check->headersAllowFraming('SAMEORIGIN', ''));
        $this->assertFalse($check->headersAllowFraming('DENY', ''));
        $this->assertFalse($check->headersAllowFraming('', "default-src 'self'; frame-ancestors 'none'"));
        $this->assertTrue($check->headersAllowFraming('', 'frame-ancestors *'));
        $this->assertTrue($check->headersAllowFraming('', ''));
    }

    public function test_the_daily_check_remembers_each_courts_answer(): void
    {
        Http::fake([
            'cdb.sci.gov.in/*' => Http::response('board', 200, ['X-Frame-Options' => 'SAMEORIGIN']),
            '*' => Http::response('board', 200),
        ]);

        $this->artisan('courts:check-boards')->assertSuccessful();

        $check = app(CourtBoardEmbedCheck::class);
        $this->assertFalse($check->isEmbeddable('https://cdb.sci.gov.in/'));
        $this->assertTrue($check->isEmbeddable('https://delhihighcourt.nic.in/app/display-board'));

        $this->get(route('dashboard'))->assertSee('value="https://delhihighcourt.nic.in/app/display-board" data-embeddable="1"', false);
    }
}
