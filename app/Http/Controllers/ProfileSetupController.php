<?php

namespace App\Http\Controllers;

use App\Services\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Guided profile setup: opens each unfinished profile page in turn until the profile is complete.
 * Every step can be skipped; skipped steps are remembered for this session.
 */
class ProfileSetupController extends Controller
{
    private const SKIPPED = 'profile_setup_skipped';

    public function __construct(private ProfileCompletion $completion) {}

    /**
     * Start (or restart) the setup at the first unfinished step.
     */
    public function start(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SKIPPED);

        return $this->continueAfter($request, null);
    }

    /**
     * Go on from a step that was just saved.
     */
    public function next(Request $request): RedirectResponse
    {
        $request->session()->reflash();

        return $this->continueAfter($request, $this->stepKey($request->query('after')));
    }

    /**
     * Leave a step for later and go on.
     */
    public function skip(Request $request, string $step): RedirectResponse
    {
        $step = $this->stepKey($step);

        if ($step) {
            $request->session()->put(self::SKIPPED, array_values(array_unique([...$request->session()->get(self::SKIPPED, []), $step])));
        }

        return $this->continueAfter($request, $step);
    }

    private function continueAfter(Request $request, ?string $current): RedirectResponse
    {
        $except = array_filter([...$request->session()->get(self::SKIPPED, []), $current]);
        $next = $this->completion->nextStep($request->user(), $except);

        if ($next) {
            return redirect()->to($next['url']);
        }

        $percent = $this->completion->forUser($request->user())['percent'];
        $request->session()->forget(self::SKIPPED);

        return redirect()
            ->route('dashboard')
            ->with('auth_login_success', $percent >= 100
                ? 'Your profile is 100% complete.'
                : "Profile setup done for now ({$percent}% complete). Finish the skipped steps any time from Home.");
    }

    private function stepKey(mixed $step): ?string
    {
        return is_string($step) && array_key_exists($step, ProfileCompletion::STEPS) ? $step : null;
    }
}
