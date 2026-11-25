<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopupsAndCourtsBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_court_page_has_a_courts_bar_for_visitors_and_advocates(): void
    {
        $bar = fn (string $html): string => (string) str($html)->after('class="vn-section-nav')->before('</nav>');

        $visitor = $bar($this->get(route('courts.index'))->assertOk()->getContent());
        foreach (['Supreme Court', 'Delhi High Court', 'High Courts', 'Delhi District Courts', 'Tribunals', 'All courts'] as $label) {
            $this->assertStringContainsString($label, $visitor);
        }
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*All courts/', $visitor);

        $this->actingAs(User::factory()->create());
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*Supreme Court/', $bar($this->get(route('courts.supreme-court'))->assertOk()->getContent()));
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*Tribunals/', $bar($this->get(route('tribunals.index'))->assertOk()->getContent()));
    }

    public function test_quick_adds_open_as_pop_ups(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('clients.index'))->assertOk()
            ->assertSee('data-sheet="'.route('sheets.client').'"', false)
            ->assertSee('data-sheet="'.e(route('sheets.client', ['type' => 'company'])).'"', false);
        $this->get(route('tasks.index'))->assertOk()->assertSee('data-sheet="'.route('sheets.task').'"', false);
        $this->get(route('cases.calendar', ['view' => 'week']))->assertOk()->assertSee('data-sheet="'.e(route('sheets.next-date', ['date' => today()->toDateString()])).'"', false);
    }

    public function test_the_client_pop_up_adds_a_company_and_the_hearing_pop_up_keeps_the_picked_day(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('sheets.client', ['type' => 'company']))->assertOk()
            ->assertSee('New company')->assertSee('name="organisation_name"', false)->assertSee('value="company"', false);
        $this->post(route('clients.store'), ['client_type' => 'company', 'organisation_name' => 'Sharma Traders', 'mobile' => '9876543210'])
            ->assertSessionHasNoErrors();
        $this->assertSame('company', Client::query()->where('organisation_name', 'Sharma Traders')->value('client_type'));

        $this->get(route('sheets.next-date', ['date' => today()->addDays(3)->toDateString()]))->assertOk()
            ->assertSee('value="'.today()->addDays(3)->toDateString().'"', false);
        $this->get(route('sheets.next-date', ['date' => today()->subDay()->toDateString()]))->assertOk()
            ->assertDontSee('value="'.today()->subDay()->toDateString().'"', false);
    }

    public function test_phones_open_the_menu_from_the_top_left_and_every_court_from_the_bottom_bar(): void
    {
        $html = $this->get(route('legal.privacy'))->assertOk()->getContent();
        $header = (string) str($html)->after('<header class="vn-main-header')->before('</header>');
        $bottomBar = (string) str($html)->after('class="vn-mobile-nav"')->before('</nav>');

        $this->assertStringContainsString('id="vn-mobile-more"', $header);
        $this->assertStringNotContainsString('vn-mobile-more', $bottomBar);
        $courts = (string) str($bottomBar)->after('vn-courts-panel')->before('</details>');
        foreach ([route('courts.supreme-court'), route('courts.delhi-high-court'), route('courts.high-courts'), route('courts.delhi-district-courts'), route('tribunals.index')] as $url) {
            $this->assertStringContainsString('href="'.$url.'"', $courts);
        }
    }

    public function test_display_boards_live_inside_each_court_not_in_the_menus(): void
    {
        $this->get(route('courts.supreme-court'))->assertOk()
            ->assertSee('href="'.route('courts.live-board', 'supreme-court').'"', false)->assertSee('Live display board');
        $this->get(route('courts.delhi-high-court'))->assertOk()
            ->assertSee('href="'.route('courts.live-board', 'delhi-high-court').'"', false);

        $courtsPage = $this->get(route('courts.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Display boards', (string) str($courtsPage)->after('class="vn-section-nav')->before('</nav>'));
        $this->assertStringNotContainsString('title="Display boards"', $courtsPage);
        $this->assertStringNotContainsString('Display boards', (string) str($courtsPage)->after('data-top-nav>')->before('</nav>'));
        $this->get(route('courts.display-boards'))->assertOk();
    }
}
