<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds standard browser security headers, including a strict Content-Security-Policy.
 *
 * Scripts only run when they come from this site or carry the per-request nonce
 * (Blade: nonce="{{ Vite::cspNonce() }}"), so injected scripts and inline event
 * handlers are blocked. Views use data attributes (resources/js/ui-actions.js) instead.
 */
class SecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Signed-in pages are never stored by the browser or a shared cache. Public location lists (States,
        // districts, cities) hold nothing private and keep the caching they set, so the pickers open fast.
        if ($request->hasSession() && $request->user() && ! $request->is('api/locations/*', 'api/court-directory/*')) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $devServer = $this->viteDevServer();
        $devSocket = $devServer ? preg_replace('/^http/', 'ws', $devServer) : '';

        $directives = [
            'default-src' => "'self'",
            'script-src' => trim("'self' 'nonce-{$nonce}' 'wasm-unsafe-eval' {$devServer}"),
            // Many views use style="" attributes, which nonces cannot cover.
            'style-src' => trim("'self' 'unsafe-inline' {$devServer}"),
            'img-src' => trim("'self' data: blob: https: {$devServer}"),
            'font-src' => trim("'self' data: {$devServer}"),
            'connect-src' => trim("'self' {$devServer} {$devSocket}"),
            'worker-src' => "'self' blob:",
            // Official court and government sites are shown in the webview.
            'frame-src' => "'self' https:",
            'frame-ancestors' => "'self'",
            'object-src' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
        ];

        return collect($directives)
            ->map(fn (string $value, string $directive): string => "{$directive} {$value}")
            ->implode('; ');
    }

    /**
     * The Vite dev server origin while `npm run dev` is running, otherwise an empty string.
     */
    private function viteDevServer(): string
    {
        $hotFile = Vite::hotFile();

        if (! is_file($hotFile)) {
            return '';
        }

        return rtrim(trim((string) file_get_contents($hotFile)), '/');
    }
}
