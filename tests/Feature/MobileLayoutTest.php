<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_reaches_iphone_safe_areas_and_the_emailed_code_takes_six_digits(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('content="width=device-width, initial-scale=1.0, viewport-fit=cover"', false)
            ->assertSee('pattern="[0-9]{6}"', false)
            ->assertSee('class="min-h-10 w-full border-0 bg-transparent px-0 text-base text-white md:text-sm', false);
    }

    public function test_visitors_see_the_footer_and_signed_in_users_do_not(): void
    {
        $this->get(route('courts.index'))->assertOk()
            ->assertSee('data-vn-footer', false)->assertDontSee('Footer links')
            ->assertSee('Create an account')->assertSee('Privacy');

        $this->actingAs(User::factory()->create());
        $this->get(route('courts.index'))->assertOk()->assertDontSee('data-vn-footer', false);
    }

    public function test_side_menu_is_short_and_each_section_leads_to_its_own_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $html = $this->get(route('cases.index'))->assertOk()->getContent();
        $sideMenu = substr($html, (int) strpos($html, 'id="vn-nav"'), (int) strpos($html, 'data-filter-empty') - (int) strpos($html, 'id="vn-nav"'));
        $accountMenu = substr($html, (int) strpos($html, 'aria-label="Account menu"'), 4000);

        // Workspace 6, Courts 6, Library 4, More 4. Tasks and the other Cases pages are in the Cases bar; boards are in each court.
        $this->assertSame(20, substr_count($sideMenu, 'class="vn-nav-link'));
        $this->assertStringContainsString('href="'.route('tribunals.index').'"', $sideMenu);
        $this->assertStringNotContainsString('Case dashboard', $sideMenu);
        $this->assertStringNotContainsString('eCourts services', $sideMenu);
        $this->assertSame(1, substr_count($sideMenu, 'href="'.route('profile.index').'"'));

        // Cases pages are tabs inside Cases; settings and appearance in the account menu.
        $this->assertStringContainsString('href="'.route('cases.calendar').'"', $html);
        $this->assertStringContainsString('data-theme-switch', $accountMenu);
        $this->assertStringContainsString('vn-header-dropdown', $accountMenu);
    }

    public function test_cases_clients_and_invoices_lists_turn_into_cards_on_phones(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
        Client::create(['client_type' => 'individual', 'first_name' => 'Ravi', 'last_name' => 'Kumar', 'status' => 'active']);

        // Cases are cards on every screen: date tile, title, numbers, court line and icon actions.
        $this->get(route('cases.index'))->assertOk()
            ->assertSee('<ul class="space-y-2.5" aria-label="Cases">', false)
            ->assertSeeInOrder(['No date', 'Asha vs State', 'Pending', 'Saket Courts', 'Download case as PDF', 'Edit case', 'Delete case']);

        $this->get(route('clients.index'))->assertOk()
            ->assertSee('class="vn-stack-table', false)
            ->assertSee('data-label="Mobile"', false);

        $this->get(route('invoices.index'))->assertOk()
            ->assertSee('class="vn-stack-table', false);
    }

    public function test_installed_app_offers_add_hearing_and_search_shortcuts(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
        $urls = array_column($manifest['shortcuts'], 'url');

        $this->assertContains('/hearings/create', $urls);
        $this->assertContains('/search', $urls);
        $this->assertSame($urls, array_values(array_filter($urls, fn (string $url) => app('router')->getRoutes()->match(request()->create($url))->getName() !== null)));
    }
}
