<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_security_policy_blocks_inline_scripts_without_the_nonce(): void
    {
        $response = $this->get(route('dashboard'))->assertOk();
        $policy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'/", $policy);
        $this->assertStringNotContainsString("'unsafe-inline' ", Str::between($policy, 'script-src', ';').' ');
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
    }

    public function test_every_page_gives_all_scripts_the_nonce_and_uses_no_inline_handlers(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ($this->pagesWithoutParameters() as $uri) {
            $response = $this->get($uri);

            if ($response->getStatusCode() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                continue;
            }

            preg_match("/'nonce-([A-Za-z0-9]+)'/", (string) $response->headers->get('Content-Security-Policy'), $nonce);
            $html = $response->getContent();

            preg_match_all('/<script\b[^>]*>/i', $html, $scriptTags);
            foreach ($scriptTags[0] as $tag) {
                $this->assertStringContainsString('nonce="'.$nonce[1].'"', $tag, "Script without nonce on {$uri}: {$tag}");
            }

            $this->assertDoesNotMatchRegularExpression('/<[^>]+\son[a-z]+\s*=\s*"/i', $html, "Inline event handler on {$uri}");
        }
    }

    /**
     * @return list<string>
     */
    private function pagesWithoutParameters(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn ($route): bool => in_array('GET', $route->methods(), true))
            ->map(fn ($route): string => '/'.ltrim($route->uri(), '/'))
            ->reject(fn (string $uri): bool => str_contains($uri, '{')
                || str_starts_with($uri, '/_')
                || str_starts_with($uri, '/storage')
                || str_contains($uri, 'logout')
                || $uri === '/up')
            ->values()
            ->all();
    }
}
