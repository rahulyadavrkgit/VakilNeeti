<?php

namespace App\Http\Controllers;

use App\Models\CaseTag;
use App\Models\CauseListAppearance;
use App\Models\Client;
use App\Models\CourtStandSide;
use App\Models\LegalCase;
use App\Models\Task;
use App\Services\CourtIdentity;
use App\Services\HearingShareText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegalCaseController extends Controller
{
    private const HEARING_FILTERS = ['all', 'today', 'tomorrow', 'next7', 'next30', 'past', 'no_upcoming'];

    /**
     * @var array<string, string>
     */
    private const HEARING_CHIPS = [
        'all' => 'All',
        'today' => 'Today',
        'tomorrow' => 'Tomorrow',
        'next7' => 'Next 7 days',
        'next30' => 'Next 30 days',
        'past' => 'Needs review',
        'no_upcoming' => 'No date',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'hearing_filter' => ['nullable', Rule::in(self::HEARING_FILTERS)],
            'tag' => ['nullable', 'integer'],
            'court' => ['nullable', 'regex:/^[A-Z]{2,4}$/'],
        ]);

        $cases = $this->filteredCases($request)
            ->with([
                'parties',
                'tags',
                'causeListAppearances.courtDocument:id,listing_date',
                'hearings' => fn ($hearingQuery) => $hearingQuery->orderBy('next_hearing_date')->orderBy('hearing_time'),
            ])
            ->withCount('hearings')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        // How many cases each hearing-date chip would show, with the same search, status and tag.
        $hearingCounts = collect(self::HEARING_CHIPS)
            ->mapWithKeys(fn (string $label, string $key): array => [$key => $this->filteredCases($request, $key)->count()])
            ->all();

        return view('cases.index', [
            'cases' => $cases,
            'hearingCounts' => $hearingCounts,
            'hearingChips' => self::HEARING_CHIPS,
            'shareText' => $cases->total() > 0 ? $this->shareText($request) : null,
            'hearingFilter' => $request->input('hearing_filter', 'all'),
            'tags' => CaseTag::query()->orderBy('name')->get(),
            'yourCourts' => $this->yourCourts(),
        ]);
    }

    /**
     * The courts this workspace has cases in, grouped by level: "Delhi HC 21", "Jalaun, UP 20"…
     * Grouped by the court part of the code ("UPJL", "DLHC", "SC"), which is what the court filter uses.
     *
     * @return list<array{key: string, level: ?string, state: ?string, district: ?string, count: int}>
     */
    private function yourCourts(): array
    {
        return LegalCase::query()
            ->whereNotNull('court_code')
            ->get(['court_code', 'court_level', 'state_code', 'district'])
            ->groupBy(fn (LegalCase $case): string => substr((string) $case->court_code, 0, 4))
            ->map(fn ($group, string $key): array => [
                'key' => $key,
                'level' => $group->first()->court_level,
                'state' => $group->first()->state_code,
                'district' => $group->pluck('district')->filter()->first(),
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->sortBy(fn (array $court): int => match ($court['level']) {
                'supreme' => 0,
                'high' => 1,
                'district' => 2,
                'tribunal' => 3,
                default => 4,
            })
            ->values()
            ->all();
    }

    /**
     * Download the filtered case list as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'hearing_filter' => ['nullable', Rule::in(self::HEARING_FILTERS)],
            'tag' => ['nullable', 'integer'],
            'court' => ['nullable', 'regex:/^[A-Z]{2,4}$/'],
        ]);

        $cases = $this->filteredCases($request)
            ->with(['tags', 'hearings' => fn ($query) => $query->orderByDesc('next_hearing_date')])
            ->orderByDesc('updated_at')
            ->get();

        return response()->streamDownload(function () use ($cases): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Case title', 'Case number', 'File No.', 'CNR', 'Court', 'Status', 'Stage', 'Filing date', 'Next hearing', 'Tags']);

            foreach ($cases as $case) {
                fputcsv($output, [
                    $case->case_title,
                    $case->case_number,
                    $case->file_number,
                    $case->cnr_number,
                    $case->court_name,
                    $case->case_status,
                    $case->stage,
                    $case->filing_date?->format('d-m-Y'),
                    optional($case->hearings->first()?->next_hearing_date)->format('d-m-Y'),
                    $case->tags->pluck('name')->implode(', '),
                ]);
            }

            fclose($output);
        }, 'my-cases-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function create(): View
    {
        return view('cases.create');
    }

    public function createManual(): View
    {
        return view('cases.form', ['case' => new LegalCase(['case_status' => 'pending'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->courtDefaults($request->validate($this->caseRules()));
        $validated['source_type'] = 'manual';

        $case = LegalCase::create($validated);

        return redirect()
            ->route('cases.show', $case)
            ->with('success', 'Case added.');
    }

    public function show(Request $request, LegalCase $case): View
    {
        $request->validate(['folder' => ['nullable', 'integer']]);

        $currentFolder = $request->filled('folder')
            ? $case->documentFolders()->findOrFail((int) $request->input('folder'))
            : null;

        $case->load([
            'parties.client',
            'tags',
            'hearings' => function ($query) {
                $query->orderByDesc('hearing_date')
                    ->orderByDesc('next_hearing_date');
            },
            'notes' => fn ($query) => $query->latest(),
            'causeListAppearances.courtDocument',
            'appointments' => fn ($query) => $query->orderBy('starts_at'),
            'expenses' => fn ($query) => $query->orderByDesc('expense_date'),
            'timeEntries' => fn ($query) => $query->orderByDesc('entry_date'),
            'documents' => fn ($query) => $query->latest(),
        ]);

        $causeListings = CauseListAppearance::upcomingForCases([$case->id]);
        $savedDate = $case->hearings->flatMap(fn ($hearing) => collect([$hearing->hearing_date, $hearing->next_hearing_date])
            ->filter()->map(fn ($date) => ['date' => $date, 'hearing' => $hearing]))
            ->filter(fn (array $entry): bool => $entry['date']->gte(today()))
            ->sortBy('date')->first();

        return view('cases.show', [
            'case' => $case,
            'tags' => CaseTag::query()->orderBy('name')->get(),
            'clients' => Client::query()->where('status', 'active')->orderBy('display_name')->orderBy('first_name')->get(['id', 'display_name', 'first_name', 'last_name']),
            'currentFolder' => $currentFolder,
            'folders' => $case->documentFolders()->where('parent_id', $currentFolder?->id)->orderBy('name')->get(),
            'folderDocuments' => $case->documents->where('folder_id', $currentFolder?->id)->values(),
            'journey' => $case->journey(),
            'standSide' => CourtStandSide::forCase($case),
            'causeListings' => $causeListings,
            'savedDate' => $savedDate,
            'followUpTeam' => CaseFollowUpController::team($case->user_id)->orderBy('name')->get(['id', 'name']),
            'followUps' => Task::query()->where('case_id', $case->id)->whereNotNull('assigned_to')
                ->with('assignee:id,name')->orderBy('due_at')->orderBy('id')->get(),
            'linkableCases' => LegalCase::query()
                ->where('user_id', $case->user_id)
                ->whereKeyNot($case->id)
                ->orderBy('case_title')
                ->get(['id', 'case_title', 'case_number', 'court_name']),
        ]);
    }

    public function edit(LegalCase $case): View
    {
        return view('cases.form', ['case' => $case]);
    }

    public function update(Request $request, LegalCase $case): RedirectResponse
    {
        $case->update($this->courtDefaults($request->validate($this->caseRules())));

        return redirect()
            ->route('cases.show', $case)
            ->with('success', 'Case updated.');
    }

    public function destroy(LegalCase $case): RedirectResponse
    {
        $case->delete();

        return redirect()
            ->route('cases.index')
            ->with('success', 'Case deleted.');
    }

    public function close(LegalCase $case): RedirectResponse
    {
        $case->update(['closed_at' => now(), 'case_status' => 'closed']);

        return back()->with('success', 'Case closed.');
    }

    public function reopen(LegalCase $case): RedirectResponse
    {
        $case->update(['closed_at' => null, 'case_status' => 'pending']);

        return back()->with('success', 'Case reopened.');
    }

    public function updateFileNumber(Request $request, LegalCase $case): RedirectResponse
    {
        $validated = $request->validate(['file_number' => ['nullable', 'string', 'max:100']]);
        $case->update($validated);

        return back()->with('success', 'File number saved.');
    }

    public function attachTag(Request $request, LegalCase $case): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:50']]);
        $tag = CaseTag::query()->firstOrCreate(['name' => trim($validated['name'])]);
        $case->tags()->syncWithoutDetaching([$tag->id]);

        return back()->with('success', "Tag “{$tag->name}” added.");
    }

    public function detachTag(LegalCase $case, CaseTag $tag): RedirectResponse
    {
        $case->tags()->detach($tag->id);

        return back()->with('success', "Tag “{$tag->name}” removed.");
    }

    /**
     * @return array<string, list<mixed>>
     */
    /**
     * Tidy the court choice: no State for the Supreme Court, no district above district level, and a
     * proper name for the Supreme Court or a High Court when no bench was typed.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function courtDefaults(array $validated): array
    {
        $level = $validated['court_level'] ?? null;

        if (! $level) {
            return $validated;
        }

        if ($level === 'supreme') {
            $validated['state_code'] = null;
        }

        if ($level !== 'district') {
            $validated['district'] = null;
        }

        if ($level !== 'supreme') {
            $validated['diary_number'] = null;
        }

        $validated['court_name'] = filled($validated['court_name'] ?? null)
            ? $validated['court_name']
            : CourtIdentity::fullName($level, $validated['state_code'] ?? null);

        return $validated;
    }

    private function caseRules(): array
    {
        return [
            'case_title' => ['required', 'string', 'max:255'],
            'case_number' => ['nullable', 'string', 'max:255'],
            'file_number' => ['nullable', 'string', 'max:100'],
            'cnr_number' => ['nullable', 'string', 'max:255'],
            'case_type' => ['nullable', 'string', 'max:255'],
            // The bench is needed for district courts and tribunals; the Supreme Court and High Courts are named for you.
            'court_name' => ['required_unless:court_level,supreme,high', 'nullable', 'string', 'max:255'],
            'court_level' => ['nullable', Rule::in(array_keys(CourtIdentity::LEVELS))],
            'state_code' => ['nullable', Rule::in(array_keys(CourtIdentity::states()))],
            'district' => ['nullable', 'string', 'max:100'],
            'diary_number' => ['nullable', 'string', 'max:30'],
            'court_complex' => ['nullable', 'string', 'max:255'],
            'court_room' => ['nullable', 'string', 'max:100'],
            'vc_link' => ['nullable', 'url:http,https', 'max:500'],
            'appearing_for' => ['nullable', Rule::in(array_keys(LegalCase::SIDES))],
            'appearing_for_parties' => ['nullable', 'string', 'max:255'],
            'judge_name' => ['nullable', 'string', 'max:255'],
            'filing_number' => ['nullable', 'string', 'max:255'],
            'filing_date' => ['nullable', 'date'],
            'disposed_on' => ['nullable', 'date'],
            'case_status' => ['required', Rule::in(LegalCase::STATUSES)],
            'stage' => ['nullable', 'string', 'max:255'],
            'petitioner_advocate' => ['nullable', 'string', 'max:255'],
            'respondent_advocate' => ['nullable', 'string', 'max:255'],
            'case_description' => ['nullable', 'string'],
        ];
    }

    /**
     * The shown cases (up to 100) as a message to send: each with its nearest date, our side,
     * court, room, judge and VC link, earliest first.
     */
    private function shareText(Request $request): string
    {
        $filter = $request->input('hearing_filter', 'all');
        $heading = match ($filter) {
            'today' => 'Cases · Today, '.today()->format('D d M Y'),
            'tomorrow' => 'Cases · Tomorrow, '.today()->addDay()->format('D d M Y'),
            'next7' => 'Cases · '.today()->format('d M').' – '.today()->addDays(6)->format('d M Y'),
            'next30' => 'Cases · '.today()->format('d M').' – '.today()->addDays(29)->format('d M Y'),
            'past' => 'Cases needing the next date',
            'no_upcoming' => 'Cases without a hearing date',
            default => 'My cases',
        };

        if ($request->filled('search')) {
            $heading .= ' · "'.trim((string) $request->input('search')).'"';
        }

        $start = $filter === 'tomorrow' ? today()->addDay() : today();
        $end = match ($filter) {
            'today', 'tomorrow' => $start,
            'next7' => $start->copy()->addDays(6),
            'next30' => $start->copy()->addDays(29),
            default => null,
        };
        $items = $this->filteredCases($request)
            ->with(['hearings', 'causeListAppearances.courtDocument:id,listing_date'])
            ->limit(100)
            ->get()
            ->map(function (LegalCase $case) use ($start, $end): array {
                // The nearest date on or after today, from either date on a hearing.
                $listings = $case->causeListAppearances->map(fn (CauseListAppearance $appearance): array => [
                    'date' => $appearance->courtDocument?->listing_date?->toDateString(),
                    'time' => null,
                    'hearing' => null,
                    'appearance' => $appearance,
                ]);
                $nearest = $case->hearings
                    ->flatMap(fn ($hearing) => [
                        ['date' => $hearing->hearing_date?->toDateString(), 'time' => $hearing->hearing_time, 'hearing' => $hearing],
                        ['date' => $hearing->next_hearing_date?->toDateString(), 'time' => null, 'hearing' => $hearing],
                    ])
                    ->concat($listings)
                    ->filter(fn (array $entry): bool => $entry['date'] !== null
                        && $entry['date'] >= $start->toDateString()
                        && (! $end || $entry['date'] <= $end->toDateString()))
                    ->sortBy(fn (array $entry): string => $entry['date'].' '.($entry['time'] ?? '99'))
                    ->first();
                $hearing = $nearest['hearing'] ?? null;
                $appearance = $nearest['appearance'] ?? null;

                return HearingShareText::fromEvent([
                    'title' => $case->case_title,
                    'case_number' => $case->case_number,
                    'appearing_for' => $case->appearing_for,
                    'appearing_for_parties' => $case->appearing_for_parties,
                    'date' => $nearest['date'] ?? null,
                    'time' => isset($nearest['time']) ? substr((string) $nearest['time'], 0, 5) : null,
                    'court' => $case->court_name,
                    'court_complex' => $case->court_complex,
                    'court_room' => $hearing?->court_room ?: ($appearance?->court_room ?: $case->court_room),
                    'judge_name' => $hearing?->judge_name ?: ($appearance?->judge_name ?: $case->judge_name),
                    'vc_link' => $hearing?->vc_link ?: ($appearance?->vcLink() ?: $case->vc_link),
                    'details' => $hearing?->hearing_stage ?: $case->stage,
                ]);
            })
            ->sortBy(fn (array $item): string => ($item['date'] ?? '9999').' '.($item['time'] ?? '99'))
            ->values();

        return HearingShareText::list($heading, $items);
    }

    /**
     * The case list query with the search, status, tag and hearing filters applied.
     */
    private function filteredCases(Request $request, ?string $hearingFilter = null): Builder
    {
        $today = now()->toDateString();
        $query = LegalCase::query();

        if ($request->filled('search')) {
            $query->search((string) $request->input('search'), (string) $request->input('search_type', 'all'));
        }

        if ($request->filled('case_status')) {
            $query->where('case_status', $request->input('case_status'));
        }

        // One court ("UPJL" = Jalaun courts, "DLHC" = Delhi High Court, "SC" = Supreme Court).
        if ($request->filled('court')) {
            $query->where('court_code', 'like', $request->string('court')->upper()->toString().'%');
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('case_tags.id', (int) $request->input('tag')));
        }

        // A hearing counts on a day when either its own date or the next date fixed at it falls on that day.
        $onDates = fn (string $from, ?string $to = null) => fn ($hearings) => $hearings->where(function ($dates) use ($from, $to) {
            foreach (['hearing_date', 'next_hearing_date'] as $column) {
                $dates->orWhere(fn ($range) => $range->whereNotNull($column)
                    ->whereDate($column, '>=', $from)
                    ->when($to, fn ($upTo) => $upTo->whereDate($column, '<=', $to)));
            }
        });
        $upcoming = $onDates($today);
        $dated = fn ($hearings) => $hearings->where(fn ($dates) => $dates->whereNotNull('hearing_date')->orWhereNotNull('next_hearing_date'));

        $listingDates = fn (string $from, ?string $to = null) => fn ($documents) => $documents
            ->whereDate('listing_date', '>=', $from)
            ->when($to, fn ($dates) => $dates->whereDate('listing_date', '<=', $to));
        $inRange = fn (string $from, string $to) => $query->where(fn ($cases) => $cases
            ->whereHas('hearings', $onDates($from, $to))
            ->orWhereHas('causeListAppearances.courtDocument', $listingDates($from, $to)));
        $listed = fn ($documents) => $documents->whereNotNull('listing_date');

        match ($hearingFilter ?? $request->input('hearing_filter', 'all')) {
            'today' => $inRange($today, $today),
            'tomorrow' => $inRange(now()->addDay()->toDateString(), now()->addDay()->toDateString()),
            // Today and the six days after it, so nothing between today and next week is missed.
            'next7' => $inRange($today, now()->addDays(6)->toDateString()),
            'next30' => $inRange($today, now()->addDays(29)->toDateString()),
            // Had a date, but every date has passed: the case status needs checking.
            'past' => $query->whereIn('case_status', ['pending', 'other'])
                ->where(fn ($cases) => $cases->whereHas('hearings', $dated)->orWhereHas('causeListAppearances.courtDocument', $listed))
                ->whereDoesntHave('hearings', $upcoming)
                ->whereDoesntHave('causeListAppearances.courtDocument', $listingDates($today)),
            // Never had a hearing date.
            'no_upcoming' => $query->whereIn('case_status', ['pending', 'other'])
                ->whereDoesntHave('hearings', $dated)
                ->whereDoesntHave('causeListAppearances.courtDocument', $listed),
            default => null,
        };

        return $query;
    }
}
