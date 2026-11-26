<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Models\Task;
use App\Services\CalendarEventService;
use App\Services\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * About this many hearings come at a time on Home; a day is never split, so a load may hold a few more.
     */
    private const HEARINGS_PER_LOAD = 10;

    /**
     * How far ahead the list reaches: every hearing saved, however far off.
     */
    private const YEARS_AHEAD = 5;

    public function index(Request $request, CalendarEventService $calendar, ProfileCompletion $profileCompletion): View
    {
        $user = $request->user();

        if (! $user) {
            return view('home');
        }

        $hearingSearch = $this->hearingSearch($request);
        [$hearingDays, $moreHearingsUrl] = $this->hearingDays($calendar, $hearingSearch, null);

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
            // Every hearing from today on, by day (each day can be shared at once); more load while scrolling.
            'hearingDays' => $hearingDays,
            'moreHearingsUrl' => $moreHearingsUrl,
            'hearingSearch' => $hearingSearch,
            'agenda' => $agenda,
            'overdueTaskCount' => $overdueTaskCount,
            'hasCases' => LegalCase::query()->exists(),
            'profile' => $profile && $profile['percent'] < 100 ? $profile : null,
        ]);
    }

    /**
     * The next hearing days after `after` (YYYY-MM-DD), as HTML for the Home list: loaded while scrolling.
     */
    public function moreHearings(Request $request, CalendarEventService $calendar): View|RedirectResponse
    {
        $request->validate(['after' => ['required', 'date_format:Y-m-d']]);

        // Opened as a page (the "More hearings" link without scripts): the calendar's list from that day on.
        if (! $request->ajax()) {
            return redirect()->route('cases.calendar', ['view' => 'list', 'date' => Carbon::parse((string) $request->query('after'))->addDay()->toDateString()]);
        }

        $hearingSearch = $this->hearingSearch($request);
        [$hearingDays, $moreHearingsUrl] = $this->hearingDays($calendar, $hearingSearch, (string) $request->query('after'));

        return view('partials.home-hearing-days', [
            'hearingDays' => $hearingDays,
            'moreHearingsUrl' => $moreHearingsUrl,
            'hearingSearch' => $hearingSearch,
            'firstLoad' => false,
        ]);
    }

    /**
     * Whole days of hearings from the day after `after` (or today), about HEARINGS_PER_LOAD at a time,
     * and the address of the next load (null at the last hearing).
     *
     * @return array{0: Collection<string, Collection<int, array<string, mixed>>>, 1: ?string}
     */
    private function hearingDays(CalendarEventService $calendar, string $search, ?string $after): array
    {
        $from = $after ? Carbon::parse($after)->addDay() : today();
        $days = $calendar->between($from, today()->addYears(self::YEARS_AHEAD), ['groups' => ['hearing'], 'search' => $search])
            ->groupBy('date');

        $page = collect();
        $count = 0;

        foreach ($days as $day => $events) {
            if ($count >= self::HEARINGS_PER_LOAD) {
                break;
            }

            $page->put($day, $events->values());
            $count += $events->count();
        }

        $more = $days->count() > $page->count()
            ? route('home.hearings', array_filter(['after' => $page->keys()->last(), 'hearing_search' => $search]))
            : null;

        return [$page, $more];
    }

    private function hearingSearch(Request $request): string
    {
        return mb_substr(trim((string) $request->query('hearing_search', '')), 0, 100);
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
