<?php

namespace App\Http\Controllers;

use App\Services\CalendarEventService;
use App\Services\HearingShareText;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CaseCalendarController extends Controller
{
    /**
     * Calendar layouts people can switch between.
     *
     * @var array<string, string>
     */
    public const VIEWS = ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'list' => 'List'];

    public function index(Request $request, CalendarEventService $calendar): View
    {
        // The person's System Preferences: which view opens, and the first day of the week.
        $defaultView = (string) $request->user()->preference('calendar_view');
        $weekStart = $request->user()->preference('week_starts_on') === 'monday' ? Carbon::MONDAY : Carbon::SUNDAY;
        $weekEnd = $weekStart === Carbon::MONDAY ? Carbon::SUNDAY : Carbon::SATURDAY;

        $viewMode = $request->query('view') === 'agenda' ? 'list' : $request->query('view', $defaultView);
        $viewMode = array_key_exists($viewMode, self::VIEWS) ? $viewMode : $defaultView;
        $date = $this->anchorDate($request);

        [$start, $end] = match ($viewMode) {
            'day' => [$date->copy(), $date->copy()],
            'week' => [$date->copy()->startOfWeek($weekStart), $date->copy()->endOfWeek($weekEnd)],
            'month' => [$date->copy()->startOfMonth()->startOfWeek($weekStart), $date->copy()->endOfMonth()->endOfWeek($weekEnd)],
            default => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
        };

        $step = match ($viewMode) {
            'day' => 'addDay',
            'week' => 'addWeek',
            default => 'addMonthNoOverflow',
        };
        $previousDate = $date->copy()->{str_replace('add', 'sub', $step)}();
        $nextDate = $date->copy()->{$step}();

        $groups = array_values(array_intersect((array) $request->query('types', []), array_keys(CalendarEventService::GROUPS)));
        $filters = [
            'court' => is_string($request->query('court')) ? $request->query('court') : null,
            'case_type' => is_string($request->query('case_type')) ? $request->query('case_type') : null,
            'groups' => $groups ?: array_keys(CalendarEventService::GROUPS),
        ];

        // Fetch the shown period and the small month picker in one go.
        $miniStart = $date->copy()->startOfMonth()->startOfWeek($weekStart);
        $miniEnd = $date->copy()->endOfMonth()->endOfWeek($weekEnd);
        $allEvents = $calendar->between($start->min($miniStart), $end->max($miniEnd), $filters);

        $events = $allEvents->filter(fn (array $event): bool => $event['date'] >= $start->toDateString() && $event['date'] <= $end->toDateString())->values();
        $eventsByDate = $events->groupBy('date');
        $miniEventDates = $allEvents->pluck('date')->unique()->flip();

        $days = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days[] = $day->copy();
        }

        $miniDays = [];
        for ($day = $miniStart->copy(); $day->lte($miniEnd); $day->addDay()) {
            $miniDays[] = $day->copy();
        }

        $periodLabel = match ($viewMode) {
            'day' => $date->format('l, d F Y'),
            'week' => $start->format('d M').' – '.$end->format('d M Y'),
            default => $date->format('F Y'),
        };

        return view('case-calendar.index', [
            'viewMode' => $viewMode,
            'date' => $date,
            'start' => $start,
            'end' => $end,
            'previousDate' => $previousDate->toDateString(),
            'nextDate' => $nextDate->toDateString(),
            'periodLabel' => $periodLabel,
            'events' => $events,
            'eventsByDate' => $eventsByDate,
            'days' => $days,
            'miniDays' => $miniDays,
            'miniEventDates' => $miniEventDates,
            'filters' => $filters,
            'selectedGroups' => $groups,
            'filterOptions' => $calendar->filterOptions(),
            'counts' => $events->countBy('group')->all(),
            'printList' => $request->boolean('print'),
            'weekdayLabels' => $weekStart === Carbon::MONDAY ? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'shareLists' => $this->shareLists($calendar, $events, $periodLabel),
            'query' => array_filter([
                'court' => $filters['court'],
                'case_type' => $filters['case_type'],
                'types' => $groups ?: null,
            ]),
        ]);
    }

    /**
     * Ready-to-send lists of hearings: what is on screen, today, tomorrow and the next 7 days.
     *
     * @param  Collection<int, array<string, mixed>>  $events
     * @return array<string, array{label: string, count: int, text: string}>
     */
    private function shareLists(CalendarEventService $calendar, Collection $events, string $periodLabel): array
    {
        $hearingsOnly = ['groups' => ['hearing']];
        $periods = [
            'view' => ['On screen · '.$periodLabel, 'Hearings · '.$periodLabel, $events],
            'today' => ['Today', 'Hearings today · '.today()->format('D d M Y'), $calendar->between(today(), today(), $hearingsOnly)],
            'tomorrow' => ['Tomorrow', 'Hearings tomorrow · '.today()->addDay()->format('D d M Y'), $calendar->between(today()->addDay(), today()->addDay(), $hearingsOnly)],
            'next7' => ['Next 7 days', 'Hearings · '.today()->format('d M').' – '.today()->addDays(6)->format('d M Y'), $calendar->between(today(), today()->addDays(6), $hearingsOnly)],
        ];

        return collect($periods)->map(fn (array $period): array => [
            'label' => $period[0],
            'count' => collect($period[2])->where('group', 'hearing')->count(),
            'text' => HearingShareText::forEvents($period[1], $period[2]),
        ])->all();
    }

    /**
     * The day the calendar is centred on: ?date=YYYY-MM-DD, the older ?month=YYYY-MM, or today.
     */
    private function anchorDate(Request $request): Carbon
    {
        foreach (['date' => '!Y-m-d', 'month' => '!Y-m'] as $parameter => $format) {
            $value = $request->query($parameter);

            if (! is_string($value)) {
                continue;
            }

            try {
                $parsed = Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($parsed && $parsed->format(ltrim($format, '!')) === $value && $parsed->year >= 1950 && $parsed->year <= 2100) {
                return $parsed;
            }
        }

        return today();
    }
}
