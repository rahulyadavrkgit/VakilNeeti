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
        ]);

        // The top card: every hearing today together; on a day without hearings, the next one. Hidden while searching.
        $todayHearings = $hearingSearch === ''
            ? $upcomingHearings->filter(fn (array $event): bool => $event['date'] === today()->toDateString())->values()
            : collect();
        $nextHearing = $hearingSearch === '' && $todayHearings->isEmpty() ? $upcomingHearings->first() : null;
        $onTop = $nextHearing ? collect([$nextHearing]) : $todayHearings;
        $isOnTop = fn (array $event): bool => $onTop->contains(fn (array $top): bool => $top['case_id'] === $event['case_id'] && $top['date'] === $event['date']);

        $agenda = $calendar->between(today(), today()->addDays(7), ['groups' => ['appointment', 'task']])
            ->reject(fn (array $event): bool => $event['is_done'] && $event['group'] === 'task')
            ->take(10);

        $overdueTaskCount = Task::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('due_at', '<', today())
            ->count();

        $profile = $request->session()->get('profile_banner_hidden') ? null : $profileCompletion->forUser($user);

        return view('home', [
            'today' => $this->todaySummary($calendar),
            'todayHearings' => $todayHearings,
            'nextHearing' => $nextHearing,
            // The rest, by day (each day can be shared at once); what the top card shows is not repeated.
            // Matters printed in a cause list become dates on their own, so they are in this list too.
            'upcomingByDay' => $upcomingHearings->reject($isOnTop)->take(12)->groupBy('date'),
            'hearingSearch' => $hearingSearch,
            'agenda' => $agenda,
            'overdueTaskCount' => $overdueTaskCount,
            'hasCases' => LegalCase::query()->exists(),
            'profile' => $profile && $profile['percent'] < 100 ? $profile : null,
        ]);
    }

    /**
     * The day at a glance: hearings today and tomorrow, and tasks due. Today's matters without a next
     * date are on My cases (App\Services\TodaysMatters).
     *
     * @return array{hearings: int, tomorrow: int, tasks: int}
     */
    private function todaySummary(CalendarEventService $calendar): array
    {
        return [
            'hearings' => $calendar->between(today(), today(), ['groups' => ['hearing']])->count(),
            'tomorrow' => $calendar->between(today()->addDay(), today()->addDay(), ['groups' => ['hearing']])->count(),
            'tasks' => Task::query()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_at')
                ->where('due_at', '<=', today()->endOfDay())
                ->count(),
        ];
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
