<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppDownloadPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_install_page_with_steps_for_each_device(): void
    {
        $this->get(route('app.download'))
            ->assertOk()
            ->assertSee('<title>Get the VakilNiti app for Android and iPhone | VakilNiti</title>', false)
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
            ->assertSee('Add to Home Screen')
            ->assertSee('Install app')
            ->assertSee('data-install-app', false)
            ->assertSee('No download from a store is needed.');
    }

    public function test_store_buttons_lead_to_the_install_page_until_store_links_are_set(): void
    {
        config(['services.mobile_apps.play_store' => null, 'services.mobile_apps.app_store' => null]);

        $this->get(route('courts.index'))
            ->assertOk()
            ->assertSee('href="'.route('app.download').'#android"', false)
            ->assertSee('href="'.route('app.download').'#iphone"', false)
            ->assertDontSee('Google Play');
    }

    public function test_store_buttons_link_to_the_stores_once_set(): void
    {
        config([
            'services.mobile_apps.play_store' => 'https://play.google.com/store/apps/details?id=com.vakilniti.app',
            'services.mobile_apps.app_store' => 'https://apps.apple.com/in/app/vakilniti/id123',
        ]);

        $this->get(route('app.download'))
            ->assertOk()
            ->assertSee('href="https://play.google.com/store/apps/details?id=com.vakilniti.app"', false)
            ->assertSee('href="https://apps.apple.com/in/app/vakilniti/id123"', false)
            ->assertSee('Google Play')
            ->assertSee('App Store')
            ->assertDontSee('No download from a store is needed.');
    }

    public function test_the_install_page_is_in_the_sitemap(): void
    {
        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee('<loc>'.route('app.download').'</loc>', false);
    }

    public function test_guest_header_offers_log_in_and_sign_up(): void
    {
        $this->get(route('courts.index'))
            ->assertOk()
            ->assertSee('images/logo-mark.svg', false)
            ->assertSee('data-vn-auth-open="login"', false)
            ->assertSee('Sign up')
            ->assertDontSee('aria-label="Account menu"', false);
    }

    public function test_signed_in_header_shows_the_account_menu_and_footer_practice_links(): void
    {
        $user = User::factory()->create(['name' => 'Asha Verma']);

        $this->actingAs($user)
            ->get(route('courts.index'))
            ->assertOk()
            ->assertSee('aria-label="Account menu"', false)
            ->assertSee('Asha Verma')
            ->assertSee('aria-label="Add new"', false)
            ->assertSee('href="'.route('billing.index').'"', false)
            ->assertDontSee('Sign up');
    }

    public function test_link_previews_use_the_large_brand_image(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('images/og-image.png').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    }
}
