<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivatePracticeAccess
{
    /**
     * Paths anyone can open without logging in.
     *
     * @var list<string>
     */
    public const PUBLIC_PATHS = [
        'login',
        'register',
        'register/*',
        'admin/login',
        'admin/initial-setup',
        'dashboard-studio/public/*',

        'police-administration',
        'police-administration/*',
        'bar-associations',
        'bar-associations/*',
        'court-fee',
        'court-fee/*',
        'bare-acts',
        'bare-acts/*',
        'new-criminal-laws',
        'new-criminal-laws/*',
        'miscellaneous-forms',
        'miscellaneous-forms/*',
        'bar-directory',
        'bar-directory/*',
        'courts',
        'courts/*',
        'sitemap.xml',
        'robots.txt',
        'shared/invoices/*',
        'court-directory',
        'court-directory/*',
        'api/court-directory/*',
        'api/locations/*',
        'court-management',
        'court-management/*',
        'court-catalogue',
        'court-catalogue/*',
        'court-integrations/ecourts',
        'court-integrations/ecourts/*',
        'webview',
        'webview/*',
    ];

    /**
     * Whether a request is for a public page: the home page or one of PUBLIC_PATHS.
     */
    public static function isPublic(Request $request): bool
    {
        return $request->path() === '/' || $request->is(...self::PUBLIC_PATHS);
    }

    public function handle(Request $request, Closure $next): Response
    {
        // The section directory is public and contains no account-specific records.
        if ($request->path() === '/') {
            return $next($request);
        }

        // Public reference modules remain accessible without login.
        foreach (self::PUBLIC_PATHS as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // Private application modules require authentication.
        if (! $request->user()) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->route('admin.login');
            }

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
