<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Models\Task;
use App\Services\CalendarEventService;
use App\Services\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CalendarEventService $calendar, ProfileCompletion $profileCompletion): View
    {
        $user = $request->user();

        if (! $user) {
            return view('home');
        }

        $hearingSearch = trim((string) $request->query('hearing_search', ''));
        $hearingSearch = mb_substr($hearingSearch, 0, 100);

        $upcomingHearings = $calendar->between(today(), today()->addDays(30), [
            'groups' => ['hearing'],
            'search' => $hearingSearch,
        ])->take(12);

        $agenda = $calendar->between(today(), today()->addDays(7), ['groups' => ['appointment', 'task']])
            ->reject(fn (array $event): bool => $event['is_done'] && $event['group'] === 'task')
            ->take(10);

        $overdueTaskCount = Task::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('due_at', '<', today())
            ->count();

        $profile = $request->session()->get('profile_banner_hidden') ? null : $profileCompletion->forUser($user);

        return view('home', [
            'upcomingHearings' => $upcomingHearings,
            'hearingSearch' => $hearingSearch,
            'agenda' => $agenda,
            'overdueTaskCount' => $overdueTaskCount,
            'hasCases' => LegalCase::query()->exists(),
            'profile' => $profile && $profile['percent'] < 100 ? $profile : null,
        ]);
    }

    /**
     * Hide the profile reminder on the home page until the user signs in again.
     */
    public function hideProfileBanner(Request $request): RedirectResponse
    {
        $request->session()->put('profile_banner_hidden', true);

        return redirect()->route('dashboard');
    }
}
