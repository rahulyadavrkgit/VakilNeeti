<?php

namespace App\Services;

use App\Models\CaseAppointment;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Collects hearings, appointments and task deadlines for the calendar, the home page and reminders.
 */
class CalendarEventService
{
    /**
     * Event groups people can filter by.
     *
     * @var array<string, string>
     */
    public const GROUPS = [
        'hearing' => 'Hearings',
        'appointment' => 'Appointments',
        'task' => 'Tasks',
    ];

    /**
     * Events between two dates (inclusive), sorted by date and time.
     *
     * @param  array{court?: ?string, case_type?: ?string, groups?: list<string>, search?: ?string, user_id?: ?int}  $filters
     * @return Collection<int, array{type: string, group: string, label: string, title: string, date: string, time: ?string, case_id: ?int, case_title: ?string, case_number: ?string, court: ?string, appearing_for?: ?string, appearing_for_parties?: ?string, court_room?: ?string, judge_name?: ?string, vc_link?: ?string, details: ?string, client_name: ?string, url: string, is_done: bool, id: int}>
     */
    public function between(CarbonInterface $start, CarbonInterface $end, array $filters = []): Collection
    {
        $groups = array_values(array_intersect($filters['groups'] ?? array_keys(self::GROUPS), array_keys(self::GROUPS)));
        $from = Carbon::instance($start)->startOfDay();
        $to = Carbon::instance($end)->endOfDay();

        $events = collect();

        if (in_array('hearing', $groups, true)) {
            $events = $events->merge($this->hearings($from, $to, $filters));
        }

        if (in_array('appointment', $groups, true)) {
            $events = $events->merge($this->appointments($from, $to, $filters));
        }

        if (in_array('task', $groups, true)) {
            $events = $events->merge($this->tasks($from, $to, $filters));
        }

        return $events
            ->sortBy(fn (array $event): string => $event['date'].' '.($event['time'] ?? '00:00').' '.$event['title'])
            ->values();
    }

    /**
     * Court names and case types used on the signed-in user's cases, for the filter dropdowns.
     *
     * @return array{courts: list<string>, case_types: list<string>}
     */
    public function filterOptions(): array
    {
        $distinct = fn (string $column): array => LegalCase::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'courts' => $distinct('court_name'),
            'case_types' => $distinct('case_type'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function hearings(Carbon $from, Carbon $to, array $filters): Collection
    {
        $query = CourtHearing::query()
            ->with('legalCase')
            ->where(function (Builder $query) use ($from, $to) {
                // whereDate compares the date part only, however the column stores it.
                foreach (['hearing_date', 'next_hearing_date'] as $column) {
                    $query->orWhere(fn (Builder $range) => $range
                        ->whereDate($column, '>=', $from->toDateString())
                        ->whereDate($column, '<=', $to->toDateString()));
                }
            });

        $this->applyCommonFilters($query, $filters, ['hearing_stage', 'judge_name', 'court_room']);

        $events = collect();

        foreach ($query->get() as $hearing) {
            $case = $hearing->legalCase;
            $base = [
                'group' => 'hearing',
                'title' => $case?->case_title ?: 'Court hearing',
                'case_id' => $hearing->case_id,
                'case_title' => $case?->case_title,
                'case_number' => $case?->case_number,
                'court' => $case?->court_name,
                'appearing_for' => $case?->appearing_for,
                'appearing_for_parties' => $case?->appearing_for_parties,
                'court_room' => $hearing->court_room ?: $case?->court_room,
                'judge_name' => $hearing->judge_name ?: $case?->judge_name,
                'vc_link' => $hearing->vc_link ?: $case?->vc_link,
                'client_name' => null,
                'url' => $case ? route('cases.show', $case) : route('hearings.history'),
                'id' => $hearing->id,
            ];

            $hearingDate = $hearing->hearing_date?->toDateString();
            $nextDate = $hearing->next_hearing_date?->toDateString();

            if ($hearingDate && $hearing->hearing_date->between($from, $to)) {
                $events->push($base + [
                    'type' => 'hearing',
                    'label' => 'Hearing',
                    'date' => $hearingDate,
                    'time' => $hearing->hearing_time ? substr((string) $hearing->hearing_time, 0, 5) : null,
                    'details' => $hearing->hearing_stage ?: $hearing->proceedings,
                    'is_done' => $hearing->hearing_date->lt(today()),
                ]);
            }

            if ($nextDate && $nextDate !== $hearingDate && $hearing->next_hearing_date->between($from, $to)) {
                $events->push($base + [
                    'type' => 'next_hearing',
                    'label' => 'Next hearing',
                    'date' => $nextDate,
                    'time' => null,
                    'details' => $hearing->next_action,
                    'is_done' => $hearing->next_hearing_date->lt(today()),
                ]);
            }
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function appointments(Carbon $from, Carbon $to, array $filters): Collection
    {
        $query = CaseAppointment::query()
            ->with('legalCase')
            ->whereBetween('starts_at', [$from, $to]);

        $this->applyCommonFilters($query, $filters, ['title', 'location']);

        return $query->get()->map(fn (CaseAppointment $appointment): array => [
            'type' => 'appointment',
            'group' => 'appointment',
            'label' => 'Appointment',
            'title' => $appointment->title,
            'date' => $appointment->starts_at->toDateString(),
            'time' => $appointment->starts_at->format('H:i'),
            'case_id' => $appointment->case_id,
            'case_title' => $appointment->legalCase?->case_title,
            'case_number' => $appointment->legalCase?->case_number,
            'court' => $appointment->legalCase?->court_name,
            'details' => $appointment->location ?: $appointment->notes,
            'client_name' => null,
            'url' => $appointment->legalCase ? route('cases.show', $appointment->legalCase).'#case-appointments' : route('cases.calendar'),
            'is_done' => $appointment->starts_at->isPast(),
            'id' => $appointment->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function tasks(Carbon $from, Carbon $to, array $filters): Collection
    {
        $query = Task::query()
            ->with(['legalCase', 'client'])
            ->whereBetween('due_at', [$from, $to])
            ->where('status', '!=', 'cancelled');

        $this->applyCommonFilters($query, $filters, ['title']);

        return $query->get()->map(fn (Task $task): array => [
            'type' => 'task',
            'group' => 'task',
            'label' => 'Task',
            'title' => $task->title,
            'date' => $task->due_at->toDateString(),
            'time' => $task->due_at->format('H:i') === '00:00' ? null : $task->due_at->format('H:i'),
            'case_id' => $task->case_id,
            'case_title' => $task->legalCase?->case_title,
            'case_number' => $task->legalCase?->case_number,
            'court' => $task->legalCase?->court_name,
            'details' => ucfirst((string) $task->priority).' priority',
            'client_name' => $task->client?->displayName(),
            'url' => route('tasks.show', $task),
            'is_done' => $task->status === 'completed',
            'id' => $task->id,
        ]);
    }

    /**
     * Owner, court, case type and search filters shared by every event type.
     *
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $searchColumns  columns on the event's own table to search
     */
    private function applyCommonFilters(Builder $query, array $filters, array $searchColumns): void
    {
        if (! empty($filters['user_id'])) {
            $query->withoutGlobalScope('user_ownership')->where($query->qualifyColumn('user_id'), $filters['user_id']);
        }

        foreach (['court' => 'court_name', 'case_type' => 'case_type'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->whereHas('legalCase', fn (Builder $case) => $case->where($column, $filters[$filter]));
            }
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }

                $query->orWhereHas('legalCase', fn (Builder $case) => $case->where(function (Builder $case) use ($search) {
                    $case->where('case_title', 'like', "%{$search}%")
                        ->orWhere('case_number', 'like', "%{$search}%")
                        ->orWhere('cnr_number', 'like', "%{$search}%")
                        ->orWhere('court_name', 'like', "%{$search}%");
                }));
            });
        }
    }
}
