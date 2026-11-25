<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->is_admin) {
            abort(403, 'Administrator access required.');
        }

        $verified = $request->session()->get('admin_verified');

        if (($verified['id'] ?? null) !== $request->user()->id
            || ($verified['version'] ?? null) !== (int) $request->user()->admin_access_version
            || ($verified['expires'] ?? 0) <= now()->timestamp) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
