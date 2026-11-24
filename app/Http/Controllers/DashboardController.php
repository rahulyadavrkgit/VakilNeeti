<?php

namespace App\Http\Controllers;

use App\Models\CauseListAppearance;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\Task;
use App\Services\CalendarEventService;
use App\Services\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        // The next hearing that has not started yet (today without a time counts); hidden while searching.
        $now = now()->format('H:i');
        $nextHearing = $hearingSearch === ''
            ? $upcomingHearings->first(fn (array $event): bool => $event['date'] > today()->toDateString() || $event['time'] === null || $event['time'] >= $now)
            : null;

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
            'upcomingHearings' => $upcomingHearings,
            'nextHearing' => $nextHearing,
            'hearingSearch' => $hearingSearch,
            'agenda' => $agenda,
            'overdueTaskCount' => $overdueTaskCount,
            'hasCases' => LegalCase::query()->exists(),
            'profile' => $profile && $profile['percent'] < 100 ? $profile : null,
        ]);
    }

    /**
     * The day at a glance: hearings today and tomorrow, tasks due, and which of today's matters
     * still need their next date. The day is "cleared" once every matter heard today has one.
     *
     * @return array{hearings: int, tomorrow: int, tasks: int, needs_next_date: list<array{case_id: int, title: string, url: string}>, cleared: bool, listed: Collection<int, CauseListAppearance>}
     */
    private function todaySummary(CalendarEventService $calendar): array
    {
        $todayHearings = $calendar->between(today(), today(), ['groups' => ['hearing']]);
        $caseIds = $todayHearings->pluck('case_id')->filter()->unique()->values();

        // A matter has its next date when any of its hearings falls after today.
        $withNextDate = $caseIds->isEmpty() ? collect() : CourtHearing::query()
            ->whereIn('case_id', $caseIds)
            ->where(fn ($dates) => $dates->whereDate('hearing_date', '>', today())->orWhereDate('next_hearing_date', '>', today()))
            ->pluck('case_id')
            ->unique();

        $needsNextDate = $todayHearings
            ->filter(fn (array $event): bool => $event['case_id'] && ! $withNextDate->contains($event['case_id']))
            ->unique('case_id')
            ->map(fn (array $event): array => ['case_id' => $event['case_id'], 'title' => $event['title'], 'url' => $event['url']])
            ->values()
            ->all();

        return [
            'hearings' => $todayHearings->count(),
            'tomorrow' => $calendar->between(today()->addDay(), today()->addDay(), ['groups' => ['hearing']])->count(),
            'tasks' => Task::query()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_at')
                ->where('due_at', '<=', today()->endOfDay())
                ->count(),
            'needs_next_date' => $needsNextDate,
            'cleared' => $todayHearings->isNotEmpty() && $needsNextDate === [],
            // Where our Delhi HC matters are printed in the court's cause list today and tomorrow (court and item).
            'listed' => CauseListAppearance::upcomingForCases(LegalCase::query()->select('id'), today()->addDay()),
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
