<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Team members (juniors and clerks) work in the owner's cases, calendar, tasks and clients, but never in
 * the owner's billing or settings. A clerk only looks, adds next dates and tasks, and manages their own
 * appearance, reminders and notifications.
 */
class EnforceTeamRole
{
    /**
     * Owner-only areas: billing and the owner's practice settings.
     *
     * @var list<string>
     */
    private const OWNER_ONLY_PATHS = [
        'billing*', 'invoices*', 'payments*', 'tds*', 'professional-tax*', 'shared/invoices*',
        'firm-profile*', 'profile/invoice-settings*', 'profile/practice-states*', 'profile/contacts*',
        'profile/tax-compliance*', 'profile/team*', 'sheets/invoices*',
    ];

    /**
     * What a clerk may change.
     *
     * @var list<string>
     */
    private const CLERK_WRITES = [
        'hearings.store', 'tasks.store', 'tasks.update', 'notifications.read-all',
        'profile.theme.update', 'profile.system-preferences.update', 'profile.calendar-link', 'profile.team.leave', 'logout', 'user.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTeamMember()) {
            return $next($request);
        }

        if ($request->is(...self::OWNER_ONLY_PATHS)) {
            abort(403, 'Only the workspace owner can open this.');
        }

        $reading = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        if ($user->isClerk() && ! $reading && ! $request->routeIs(...self::CLERK_WRITES)) {
            abort(403, 'A clerk can add next dates and tasks, but not change this.');
        }

        return $next($request);
    }
}
