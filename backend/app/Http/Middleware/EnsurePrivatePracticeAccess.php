<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivatePracticeAccess
{
    private array $publicPaths = [
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
        'court-directory',
        'court-directory/*',
        'api/court-directory/*',
        'court-management',
        'court-management/*',
        'court-catalogue',
        'court-catalogue/*',
        'court-integrations/ecourts',
        'court-integrations/ecourts/*',
        'webview',
        'webview/*',
    ];

    private array $restrictedForOrdinaryUsers = [
        'profile',
        'profile/*',
        'firm-profile',
        'firm-profile/*',
        'practice-states',
        'practice-states/*',
        'personal-profile',
        'personal-profile/*',
        'professional-profile',
        'professional-profile/*',
        'profile-contacts',
        'profile-contacts/*',
        'invoice-settings',
        'invoice-settings/*',
        'tax-compliance',
        'tax-compliance/*',
        'professional-tax',
        'professional-tax/*',
        'tds',
        'tds/*',
        'case-calendar',
        'case-calendar/*',
        'hearing-history',
        'hearing-history/*',
        'case-dashboard',
        'case-dashboard/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Public reference modules remain accessible without login.
        foreach ($this->publicPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // Private application modules require authentication.
        if (!$request->user()) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->route('admin.login');
            }

            return redirect()->route('login');
        }

        // Administrators retain access to all private modules.
        if ($request->user()->is_admin) {
            return $next($request);
        }

        // Temporarily restrict sensitive modules until raw-query
        // ownership enforcement has been completed.
        foreach ($this->restrictedForOrdinaryUsers as $path) {
            if ($request->is($path)) {
                abort(
                    403,
                    'This feature is temporarily unavailable for ordinary accounts while individual data protection is being completed.'
                );
            }
        }

        return $next($request);
    }
}