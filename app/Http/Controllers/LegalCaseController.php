<?php

namespace App\Http\Controllers;

use App\Models\CaseTag;
use App\Models\Client;
use App\Models\CourtStandSide;
use App\Models\LegalCase;
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
     * The hearing-date chips on the case list. "next30" still works in links but has no chip:
     * the calendar covers the month, and the chip only repeated "All" for most diaries.
     *
     * @var array<string, string>
     */
    private const HEARING_CHIPS = [
        'all' => 'All',
        'today' => 'Today',
        'tomorrow' => 'Tomorrow',
        'next7' => 'Next 7 days',
        'past' => 'Needs review',
        'no_upcoming' => 'No date yet',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'hearing_filter' => ['nullable', Rule::in(self::HEARING_FILTERS)],
            'tag' => ['nullable', 'integer'],
        ]);

        $cases = $this->filteredCases($request)
            ->with([
                'parties',
                'tags',
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
        ]);
    }

    /**
     * Download the filtered case list as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'hearing_filter' => ['nullable', Rule::in(self::HEARING_FILTERS)],
            'tag' => ['nullable', 'integer'],
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
        $validated = $request->validate($this->caseRules());
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
            'appointments' => fn ($query) => $query->orderBy('starts_at'),
            'expenses' => fn ($query) => $query->orderByDesc('expense_date'),
            'timeEntries' => fn ($query) => $query->orderByDesc('entry_date'),
            'documents' => fn ($query) => $query->latest(),
        ]);

        return view('cases.show', [
            'case' => $case,
            'tags' => CaseTag::query()->orderBy('name')->get(),
            'clients' => Client::query()->where('status', 'active')->orderBy('display_name')->orderBy('first_name')->get(['id', 'display_name', 'first_name', 'last_name']),
            'currentFolder' => $currentFolder,
            'folders' => $case->documentFolders()->where('parent_id', $currentFolder?->id)->orderBy('name')->get(),
            'folderDocuments' => $case->documents->where('folder_id', $currentFolder?->id)->values(),
            'journey' => $case->journey(),
            'standSide' => CourtStandSide::forCase($case),
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
        $case->update($request->validate($this->caseRules()));

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
    private function caseRules(): array
    {
        return [
            'case_title' => ['required', 'string', 'max:255'],
            'case_number' => ['nullable', 'string', 'max:255'],
            'file_number' => ['nullable', 'string', 'max:100'],
            'cnr_number' => ['nullable', 'string', 'max:255'],
            'case_type' => ['nullable', 'string', 'max:255'],
            'court_name' => ['required', 'string', 'max:255'],
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

        $today = today()->toDateString();
        $items = $this->filteredCases($request)
            ->with('hearings')
            ->limit(100)
            ->get()
            ->map(function (LegalCase $case) use ($today): array {
                // The nearest date on or after today, from either date on a hearing.
                $nearest = $case->hearings
                    ->flatMap(fn ($hearing) => [
                        ['date' => $hearing->hearing_date?->toDateString(), 'time' => $hearing->hearing_time, 'hearing' => $hearing],
                        ['date' => $hearing->next_hearing_date?->toDateString(), 'time' => null, 'hearing' => $hearing],
                    ])
                    ->filter(fn (array $entry): bool => $entry['date'] !== null && $entry['date'] >= $today)
                    ->sortBy(fn (array $entry): string => $entry['date'].' '.($entry['time'] ?? '99'))
                    ->first();
                $hearing = $nearest['hearing'] ?? null;

                return HearingShareText::fromEvent([
                    'title' => $case->case_title,
                    'case_number' => $case->case_number,
                    'appearing_for' => $case->appearing_for,
                    'appearing_for_parties' => $case->appearing_for_parties,
                    'date' => $nearest['date'] ?? null,
                    'time' => isset($nearest['time']) ? substr((string) $nearest['time'], 0, 5) : null,
                    'court' => $case->court_name,
                    'court_complex' => $case->court_complex,
                    'court_room' => $hearing?->court_room ?: $case->court_room,
                    'judge_name' => $hearing?->judge_name ?: $case->judge_name,
                    'vc_link' => $hearing?->vc_link ?: $case->vc_link,
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

        match ($hearingFilter ?? $request->input('hearing_filter', 'all')) {
            'today' => $query->whereHas('hearings', $onDates($today, $today)),
            'tomorrow' => $query->whereHas('hearings', $onDates(now()->addDay()->toDateString(), now()->addDay()->toDateString())),
            // Today and the six days after it, so nothing between today and next week is missed.
            'next7' => $query->whereHas('hearings', $onDates($today, now()->addDays(6)->toDateString())),
            'next30' => $query->whereHas('hearings', $onDates($today, now()->addDays(29)->toDateString())),
            // Had a date, but every date has passed: the case status needs checking.
            'past' => $query->whereHas('hearings', $dated)->whereDoesntHave('hearings', $upcoming),
            // Never had a hearing date.
            'no_upcoming' => $query->whereDoesntHave('hearings', $dated),
            default => null,
        };

        return $query;
    }
}
