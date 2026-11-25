<?php

namespace App\Services;

use App\Models\CaseAppointment;
use App\Models\CaseDocument;
use App\Models\CauseListAppearance;
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
     * @return Collection<int, array{type: string, group: string, label: string, title: string, date: string, time: ?string, case_id: ?int, case_title: ?string, case_number: ?string, court: ?string, appearing_for?: ?string, appearing_for_parties?: ?string, court_complex?: ?string, court_room?: ?string, judge_name?: ?string, vc_link?: ?string, details: ?string, client_name: ?string, url: string, is_done: bool, id: int}>
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

        $orders = CaseDocument::latestOrdersForCases($events->where('group', 'hearing')->pluck('case_id')->filter()->unique());

        return $events
            ->map(fn (array $event): array => $event + ['order' => $event['group'] === 'hearing' ? $orders->get($event['case_id']) : null])
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
                'date_source' => $hearing->dateSourceLabel(),
                'source_updated_at' => $hearing->updated_at,
                'case_title' => $case?->case_title,
                'case_number' => $case?->case_number,
                'court' => $case?->court_name,
                'court_complex' => $case?->court_complex,
                'court_level' => $case?->court_level,
                'state_code' => $case?->state_code,
                'district' => $case?->district,
                'appearing_for' => $case?->appearing_for,
                'appearing_for_parties' => $case?->appearing_for_parties,
                'court_room' => $hearing->court_room ?: $case?->court_room,
                'item_number' => $hearing->item_number,
                'list_total' => $hearing->list_total,
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
                // The item and list size noted with a hearing belong to that hearing's own date.
                $events->push(['item_number' => null, 'list_total' => null] + $base + [
                    'type' => 'next_hearing',
                    'label' => 'Next hearing',
                    'date' => $nextDate,
                    'time' => null,
                    'details' => $hearing->next_action,
                    'is_done' => $hearing->next_hearing_date->lt(today()),
                ]);
            }
        }

        return $this->withCauseLists($events, $from, $to, $filters);
    }

    /**
     * Each hearing with where its case is printed in a court's cause list that day (court, item, the
     * board facts for that court, list name, bench), when one of the cause-list checks found it. A case
     * found in a cause list on a day without a saved hearing is added as its own entry ("Listed").
     *
     * @param  Collection<int, array<string, mixed>>  $events
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function withCauseLists(Collection $events, Carbon $from, Carbon $to, array $filters): Collection
    {
        // The workspace's cases (same court, case type and search filters as the rest of the calendar).
        $cases = LegalCase::query();
        $this->applyCaseFilters($cases, $filters);

        $listings = CauseListAppearance::query()
            ->with('courtDocument:id,provider_key,listing_date,document_label,source_url,fetched_at')
            ->whereIn('legal_case_id', $events->pluck('case_id')->filter()->unique()->values()->merge((clone $cases)->select('id')->pluck('id'))->unique()->values())
            ->whereHas('courtDocument', fn ($documents) => $documents
                ->whereDate('listing_date', '>=', $from->toDateString())
                ->whereDate('listing_date', '<=', $to->toDateString()))
            ->get()
            // The latest list read for a day wins (a supplementary list after the main one).
            ->sortBy(fn (CauseListAppearance $listing): array => [$listing->listPriority(), $listing->id])
            ->keyBy(fn (CauseListAppearance $listing): string => $listing->legal_case_id.'|'.$listing->courtDocument->listing_date->toDateString());

        $events = $events->sortBy(fn (array $event): array => [
            $event['type'] === 'hearing' ? 0 : 1, $event['time'] ?? '99:99', $event['item_number'] ? 0 : 1, -$event['id'],
        ])->unique(fn (array $event): string => ($event['case_id'] ?? 'hearing-'.$event['id']).'|'.$event['date'])->values();
        $events = $events->map(fn (array $event): array => $event + ['cause_list' => ($listings[$event['case_id'].'|'.$event['date']] ?? null)?->stripData()]);
        $covered = $events->map(fn (array $event): string => $event['case_id'].'|'.$event['date'])->flip();
        $listedOnly = $listings->reject(fn (CauseListAppearance $listing, string $key): bool => $covered->has($key));

        if ($listedOnly->isEmpty()) {
            return $events;
        }

        $listedCases = (clone $cases)->whereIn('id', $listedOnly->pluck('legal_case_id')->unique())->get()->keyBy('id');

        foreach ($listedOnly as $listing) {
            $case = $listedCases[$listing->legal_case_id] ?? null;

            if (! $case) {
                continue;
            }

            $date = $listing->courtDocument->listing_date;
            $events->push([
                'group' => 'hearing', 'type' => 'listed', 'label' => 'Listed in the cause list',
                'title' => $case->case_title ?: 'Court hearing', 'case_id' => $case->id,
                'date_source' => 'From the cause list', 'source_updated_at' => $listing->courtDocument->fetched_at,
                'case_title' => $case->case_title, 'case_number' => $case->case_number,
                'court' => $case->court_name, 'court_complex' => $case->court_complex,
                'court_level' => $case->court_level, 'state_code' => $case->state_code, 'district' => $case->district,
                'appearing_for' => $case->appearing_for, 'appearing_for_parties' => $case->appearing_for_parties,
                'court_room' => $case->court_room, 'item_number' => null, 'list_total' => null,
                'judge_name' => $case->judge_name, 'vc_link' => $listing->vcLink() ?: $case->vc_link, 'client_name' => null,
                'url' => route('cases.show', $case), 'id' => 'listed-'.$listing->id,
                'date' => $date->toDateString(), 'time' => null, 'details' => null, 'is_done' => $date->lt(today()),
                'cause_list' => $listing->stripData(),
            ]);
        }

        return $events;
    }

    /**
     * The calendar's owner, court, case type and search filters, on a cases query.
     *
     * @param  Builder<LegalCase>  $cases
     * @param  array<string, mixed>  $filters
     */
    private function applyCaseFilters(Builder $cases, array $filters): void
    {
        if (! empty($filters['user_id'])) {
            $cases->withoutGlobalScope('user_ownership')->where('user_id', $filters['user_id']);
        }

        foreach (['court' => 'court_name', 'case_type' => 'case_type'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $cases->where($column, $filters[$filter]);
            }
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $cases->where(fn (Builder $case) => $case->where('case_title', 'like', "%{$search}%")
                ->orWhere('case_number', 'like', "%{$search}%")
                ->orWhere('cnr_number', 'like', "%{$search}%")
                ->orWhere('court_name', 'like', "%{$search}%"));
        }
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
