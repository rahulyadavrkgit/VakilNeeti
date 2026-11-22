<?php

namespace App\Http\Controllers;

use App\Models\CaseTag;
use App\Models\Client;
use App\Models\LegalCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegalCaseController extends Controller
{
    private const HEARING_FILTERS = ['all', 'today', 'tomorrow', 'next7', 'next30', 'past', 'no_upcoming'];

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

        return view('cases.index', [
            'cases' => $cases,
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
     * The case list query with the search, status, tag and hearing filters applied.
     */
    private function filteredCases(Request $request): Builder
    {
        $today = now()->toDateString();
        $query = LegalCase::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $searchType = $request->input('search_type', 'all');

            $query->where(function ($q) use ($search, $searchType) {
                match ($searchType) {
                    'cnr' => $q->where('cnr_number', 'like', "%{$search}%"),
                    'case_number' => $q->where('case_number', 'like', "%{$search}%"),
                    'party_name' => $q->whereHas('parties', fn ($partyQuery) => $partyQuery->where('party_name', 'like', "%{$search}%")),
                    'case_title' => $q->where('case_title', 'like', "%{$search}%"),
                    default => $q->where('case_title', 'like', "%{$search}%")
                        ->orWhere('case_number', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%")
                        ->orWhere('cnr_number', 'like', "%{$search}%")
                        ->orWhere('court_name', 'like', "%{$search}%")
                        ->orWhereHas('parties', fn ($partyQuery) => $partyQuery->where('party_name', 'like', "%{$search}%")),
                };
            });
        }

        if ($request->filled('case_status')) {
            $query->where('case_status', $request->input('case_status'));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('case_tags.id', (int) $request->input('tag')));
        }

        $upcoming = fn ($q) => $q->whereNotNull('next_hearing_date')->whereDate('next_hearing_date', '>=', $today);

        match ($request->input('hearing_filter', 'all')) {
            'today' => $query->whereHas('hearings', fn ($q) => $q->whereDate('next_hearing_date', $today)),
            'tomorrow' => $query->whereHas('hearings', fn ($q) => $q->whereDate('next_hearing_date', now()->addDay()->toDateString())),
            'next7' => $query->whereHas('hearings', fn ($q) => $q->whereDate('next_hearing_date', '>', $today)->whereDate('next_hearing_date', '<=', now()->addDays(7)->toDateString())),
            'next30' => $query->whereHas('hearings', fn ($q) => $q->whereDate('next_hearing_date', '>=', $today)->whereDate('next_hearing_date', '<=', now()->addDays(30)->toDateString())),
            'past' => $query->whereHas('hearings', fn ($q) => $q->whereNotNull('next_hearing_date')->whereDate('next_hearing_date', '<', $today))->whereDoesntHave('hearings', $upcoming),
            'no_upcoming' => $query->whereDoesntHave('hearings', $upcoming),
            default => null,
        };

        return $query;
    }
}
