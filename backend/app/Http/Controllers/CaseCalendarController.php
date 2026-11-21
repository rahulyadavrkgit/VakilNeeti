<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CaseCalendarController extends Controller
{
    public function index(Request $request)
    {
        $requestedMonth = $request->query('month', now()->format('Y-m'));

        try {
            $month = Carbon::createFromFormat('!Y-m', $requestedMonth);

            if (!$month || $month->format('Y-m') !== $requestedMonth) {
                $month = now()->startOfMonth();
            }
        } catch (\Throwable $exception) {
            $month = now()->startOfMonth();
        }

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $previousMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        $events = [];

        // Court hearing records
        $hearingRows = DB::table('court_hearings as h')
            ->leftJoin('legal_cases as lc', function ($join) {
                $join->on('lc.id', '=', 'h.case_id');
                if (!auth()->user()->is_admin) {
                    $join->where('lc.user_id', auth()->id());
                }
            })
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('h.user_id', auth()->id()))
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('h.hearing_date', [
                    $start->toDateString(),
                    $end->toDateString()
                ])->orWhereBetween('h.next_hearing_date', [
                    $start->toDateString(),
                    $end->toDateString()
                ]);
            })
            ->select(
                'h.id',
                'h.case_id',
                'h.hearing_date',
                'h.hearing_time',
                'h.hearing_stage',
                'h.proceedings',
                'h.next_hearing_date',
                'h.next_action',
                'lc.case_title',
                'lc.case_number'
            )
            ->get();

        foreach ($hearingRows as $hearing) {
            $caseTitle = $hearing->case_title ?: 'Court hearing';

            if (
                $hearing->hearing_date &&
                $hearing->hearing_date >= $start->toDateString() &&
                $hearing->hearing_date <= $end->toDateString()
            ) {
                $events[] = [
                    'type' => 'hearing',
                    'label' => 'Hearing',
                    'title' => $caseTitle,
                    'date' => $hearing->hearing_date,
                    'time' => $hearing->hearing_time,
                    'case_id' => $hearing->case_id,
                    'case_number' => $hearing->case_number,
                    'details' => $hearing->hearing_stage ?: $hearing->proceedings,
                    'id' => $hearing->id,
                ];
            }

            if (
                $hearing->next_hearing_date &&
                $hearing->next_hearing_date >= $start->toDateString() &&
                $hearing->next_hearing_date <= $end->toDateString() &&
                $hearing->next_hearing_date !== $hearing->hearing_date
            ) {
                $events[] = [
                    'type' => 'next_hearing',
                    'label' => 'Next Hearing',
                    'title' => $caseTitle,
                    'date' => $hearing->next_hearing_date,
                    'time' => null,
                    'case_id' => $hearing->case_id,
                    'case_number' => $hearing->case_number,
                    'details' => $hearing->next_action,
                    'id' => $hearing->id,
                ];
            }
        }

        // Task deadlines
        $taskRows = DB::table('tasks as t')
            ->leftJoin('clients as c', function ($join) {
                $join->on('c.id', '=', 't.client_id');
                if (!auth()->user()->is_admin) {
                    $join->where('c.user_id', auth()->id());
                }
            })
            ->leftJoin('legal_cases as lc', function ($join) {
                $join->on('lc.id', '=', 't.case_id');
                if (!auth()->user()->is_admin) {
                    $join->where('lc.user_id', auth()->id());
                }
            })
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('t.user_id', auth()->id()))
            ->whereBetween('t.due_at', [
                $start->startOfDay()->toDateTimeString(),
                $end->endOfDay()->toDateTimeString()
            ])
            ->select(
                't.id',
                't.title',
                't.due_at',
                't.priority',
                't.status',
                't.case_id',
                't.client_id',
                'c.display_name',
                'c.first_name',
                'c.last_name',
                'lc.case_title',
                'lc.case_number'
            )
            ->get();

        foreach ($taskRows as $task) {
            $clientName = $task->display_name
                ?: trim(($task->first_name ?? '') . ' ' . ($task->last_name ?? ''));

            $events[] = [
                'type' => 'task',
                'label' => 'Task',
                'title' => $task->title,
                'date' => Carbon::parse($task->due_at)->toDateString(),
                'time' => Carbon::parse($task->due_at)->format('H:i'),
                'case_id' => $task->case_id,
                'case_number' => $task->case_number,
                'case_title' => $task->case_title,
                'client_name' => $clientName,
                'details' => 'Priority: ' . ucfirst($task->priority)
                    . ' | Status: ' . ucfirst(str_replace('_', ' ', $task->status)),
                'id' => $task->id,
                'status' => $task->status,
            ];
        }

        $appointmentRows = DB::table('case_appointments as a')
            ->leftJoin('legal_cases as lc', function ($join) {
                $join->on('lc.id', '=', 'a.case_id');
                if (!auth()->user()->is_admin) {
                    $join->where('lc.user_id', auth()->id());
                }
            })
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('a.user_id', auth()->id()))
            ->whereBetween('a.starts_at', [
                $start->copy()->startOfDay()->toDateTimeString(),
                $end->copy()->endOfDay()->toDateTimeString(),
            ])
            ->select(
                'a.id',
                'a.title',
                'a.starts_at',
                'a.location',
                'a.notes',
                'a.case_id',
                'lc.case_title',
                'lc.case_number'
            )
            ->get();

        foreach ($appointmentRows as $appointment) {
            $events[] = [
                'type' => 'appointment',
                'label' => 'Appointment',
                'title' => $appointment->title,
                'date' => Carbon::parse($appointment->starts_at)->toDateString(),
                'time' => Carbon::parse($appointment->starts_at)->format('H:i'),
                'case_id' => $appointment->case_id,
                'case_number' => $appointment->case_number,
                'case_title' => $appointment->case_title,
                'details' => $appointment->location ?: $appointment->notes,
                'id' => $appointment->id,
            ];
        }

        $eventsByDate = collect($events)
            ->sortBy(function ($event) {
                return $event['date'] . ' ' . ($event['time'] ?: '00:00');
            })
            ->groupBy('date');

        // Build complete Sunday-to-Saturday calendar grid.
        $calendarStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);

        $calendarDays = [];

        for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) {
            $calendarDays[] = $day->copy();
        }

        $viewMode = $request->query('view', 'month');

        if (!in_array($viewMode, ['month', 'agenda'], true)) {
            $viewMode = 'month';
        }

        return view('case-calendar.index', compact(
            'month',
            'previousMonth',
            'nextMonth',
            'eventsByDate',
            'calendarDays',
            'viewMode'
        ));
    }

    public function history(Request $request)
    {
        $query = DB::table('court_hearings as h')
            ->leftJoin('legal_cases as lc', function ($join) {
                $join->on('lc.id', '=', 'h.case_id');
                if (!auth()->user()->is_admin) {
                    $join->where('lc.user_id', auth()->id());
                }
            })
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('h.user_id', auth()->id()))
            ->select(
                'h.id',
                'h.case_id',
                'h.hearing_date',
                'h.hearing_time',
                'h.court_room',
                'h.judge_name',
                'h.hearing_stage',
                'h.proceedings',
                'h.next_action',
                'h.next_hearing_date',
                'lc.case_title',
                'lc.case_number',
                'lc.cnr_number',
                'lc.court_name'
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
    }}