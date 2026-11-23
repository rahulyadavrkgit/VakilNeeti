<?php

namespace App\Http\Controllers;

use App\Services\CalendarEventService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $viewMode = $request->query('view') === 'agenda' ? 'list' : $request->query('view', 'month');
        $viewMode = array_key_exists($viewMode, self::VIEWS) ? $viewMode : 'month';
        $date = $this->anchorDate($request);

        [$start, $end] = match ($viewMode) {
            'day' => [$date->copy(), $date->copy()],
            'week' => [$date->copy()->startOfWeek(Carbon::SUNDAY), $date->copy()->endOfWeek(Carbon::SATURDAY)],
            'month' => [$date->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY), $date->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY)],
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
        $miniStart = $date->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $miniEnd = $date->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
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
            'query' => array_filter([
                'court' => $filters['court'],
                'case_type' => $filters['case_type'],
                'types' => $groups ?: null,
            ]),
        ]);
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

    public function history(Request $request)
    {
        $query = DB::table('court_hearings as h')
            ->leftJoin('legal_cases as lc', function ($join) {
                $join->on('lc.id', '=', 'h.case_id');
                if (! auth()->user()->is_admin) {
                    $join->where('lc.user_id', auth()->id());
                }
            })
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('h.user_id', auth()->id()))
            ->select(
                'h.id',
                'h.case_id',
                'h.hearing_date',
                'h.hearing_time',
                'h.court_room',
                'h.vc_link as hearing_vc_link',
                'h.judge_name',
                'h.hearing_stage',
                'h.proceedings',
                'h.next_action',
                'h.next_hearing_date',
                'lc.case_title',
                'lc.case_number',
                'lc.cnr_number',
                'lc.court_name',
                'lc.court_complex',
                'lc.appearing_for',
                'lc.appearing_for_parties',
                'lc.court_room as case_court_room',
                'lc.judge_name as case_judge_name',
                'lc.vc_link'
            );

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('lc.case_title', 'like', "%{$search}%")
                    ->orWhere('lc.case_number', 'like', "%{$search}%")
                    ->orWhere('lc.cnr_number', 'like', "%{$search}%")
                    ->orWhere('h.judge_name', 'like', "%{$search}%")
                    ->orWhere('h.hearing_stage', 'like', "%{$search}%");
            });
        }

        $hearings = $query
            ->orderByDesc('h.hearing_date')
            ->orderByDesc('h.id')
            ->paginate(20)
            ->withQueryString();

        return view('hearing-history.index', compact('hearings'));
    }
}
