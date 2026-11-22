<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_a_descriptive_title_description_canonical_and_structured_data(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<title>VakilNiti: legal practice workspace for Indian advocates</title>', false)
            ->assertSee('<meta name="description" content="Case diary with hearings', false)
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('"@type":"WebSite"', false);
    }

    public function test_public_pages_use_their_own_title_and_description(): void
    {
        $this->get(route('courts.delhi-district-courts'))
            ->assertOk()
            ->assertSee('<title>Delhi District Courts | VakilNiti</title>', false)
            ->assertSee('<meta name="description" content="Delhi District Courts by court complex', false)
            ->assertSee('<link rel="canonical" href="'.route('courts.delhi-district-courts').'">', false);
    }

    public function test_canonical_keeps_only_meaningful_query_values(): void
    {
        $this->get(route('court-directory.index', ['level' => 'high-court', 'utm_source' => 'x']))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('court-directory.index').'?level=high-court">', false);
    }

    public function test_private_and_login_pages_are_not_indexed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('cases.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('rel="canonical"', false);
    }

    public function test_robots_txt_points_to_the_sitemap_and_blocks_private_areas(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('seo.sitemap'))
            ->assertSee('Disallow: /cases')
            ->assertSee('Disallow: /clients');
    }

    public function test_sitemap_lists_every_public_page_and_each_one_opens(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        foreach (array_keys(SeoController::SITEMAP_ROUTES) as $route) {
            $response->assertSee('<loc>'.e(route($route)).'</loc>', false);
            $this->assertSame(200, $this->get(route($route))->status(), $route);
        }
    }
}
