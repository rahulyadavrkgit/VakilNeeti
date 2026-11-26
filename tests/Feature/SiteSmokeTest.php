<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Opens every page that needs no parameters, as a guest and as a signed-in advocate,
 * and fails on any server error. Catches broken views and missing data early.
 */
class SiteSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pages that need outside services or files and are tested elsewhere.
     *
     * @var list<string>
     */
    private const SKIPPED = ['storage/{path}', 'up'];

    public function test_no_page_returns_a_server_error_for_guests_or_signed_in_users(): void
    {
        $uris = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => in_array('GET', $route->methods(), true)
                && ! str_contains($route->uri(), '{')
                && ! str_starts_with($route->uri(), '_')
                && ! in_array($route->uri(), self::SKIPPED, true))
            ->map(fn (Route $route): string => '/'.ltrim($route->uri(), '/'))
            ->unique()
            ->values();

        $this->assertGreaterThan(50, $uris->count());

        $failures = [];

        foreach ($uris as $uri) {
            $status = $this->get($uri)->getStatusCode();

            if ($status >= 500) {
                $failures[] = "guest {$uri} → {$status}";
            }
        }

        $this->actingAs(User::factory()->create(['account_type' => 'individual']));

        foreach ($uris as $uri) {
            $status = $this->get($uri)->getStatusCode();

            if ($status >= 500) {
                $failures[] = "advocate {$uri} → {$status}";
            }
        }

        $this->assertSame([], $failures);
    }
}
