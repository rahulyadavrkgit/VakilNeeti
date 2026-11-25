<?php

namespace App\Http\Controllers;

use App\Models\CourtRecordImport;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\EcourtsResultParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Guided "find my cases": the advocate searches eCourts by bar code themselves (solving the
 * CAPTCHA there), copies the result list and pastes it here. VakilNiti never scrapes eCourts.
 */
class CaseFinderController extends Controller
{
    /**
     * Official search pages, by court level.
     *
     * @var array<string, array{label: string, url: string, steps: list<string>}>
     */
    public const PORTALS = [
        'district' => [
            'label' => 'District & Taluka courts',
            'url' => 'https://services.ecourts.gov.in/ecourtindia_v6/',
            'steps' => [
                'Open Case Status and choose the Advocate tab.',
                'Choose your State, District and Court Complex.',
                'Choose Bar Code, then enter the State code, number and year shown here.',
                'Type the CAPTCHA you see and press Go.',
            ],
        ],
        'high_court' => [
            'label' => 'High Court',
            'url' => 'https://hcservices.ecourts.gov.in/hcservices/main.php',
            'steps' => [
                'Choose your High Court and bench.',
                'Open Case Status and choose Advocate.',
                'Search by your name or bar code as the High Court allows.',
                'Type the CAPTCHA you see and press Go.',
            ],
        ],
        'supreme_court' => [
            'label' => 'Supreme Court',
            'url' => 'https://www.sci.gov.in/',
            'steps' => [
                'Open Case Status and choose the Advocate / AOR search.',
                'Enter your details and type the CAPTCHA you see.',
            ],
        ],
    ];

    public function index(Request $request): View
    {
        $profile = $request->user()->advocateProfile;

        return view('cases.find', [
            'profile' => $profile,
            'portals' => self::PORTALS,
            // A court picked in the "Add case" pop-up arrives as ?level=…&court_name=….
            'level' => array_key_exists($level = old('level', $request->query('level', 'district')), self::PORTALS) ? $level : 'district',
            'method' => in_array($method = old('method', $request->query('method', 'app')), ['app', 'website', 'calendar'], true) ? $method : 'app',
            'defaultCourt' => old('court_name', Str::limit((string) $request->query('court_name', $profile?->court), 255, '')),
        ]);
    }

    public function preview(Request $request, EcourtsResultParser $parser): View|RedirectResponse
    {
        $validated = $request->validate([
            'level' => ['required', Rule::in(array_keys(self::PORTALS))],
            'court_name' => ['nullable', 'string', 'max:255'],
            'results' => ['required', 'string', 'max:200000'],
            'retry' => ['nullable', 'integer', 'min:1'],
        ], [
            'results.required' => 'Paste the results you copied from eCourts.',
        ]);

        $retry = CourtRecordImport::retryParent($validated['retry'] ?? null, 'website');
        $rows = $parser->parse($validated['results'], $validated['court_name'] ?? null);

        if ($rows === []) {
            $this->recordPastedAttempt('failed', $retry?->id, ['reason' => 'No case numbers were found in the pasted eCourts results.']);

            return back()->withInput()->withErrors([
                'results' => 'No case numbers were found. Copy the whole results table from eCourts (case numbers look like O.S./123/2019) and paste it again.',
            ]);
        }

        $existing = LegalCase::query()->get(['case_number', 'cnr_number', 'court_name']);

        foreach ($rows as &$row) {
            $row['already_added'] = $existing->contains(fn (LegalCase $case): bool => ($row['cnr'] && strcasecmp((string) $case->cnr_number, $row['cnr']) === 0)
                || (strcasecmp((string) $case->case_number, $row['full_number']) === 0 && (! $row['court'] || strcasecmp((string) $case->court_name, $row['court']) === 0)));
        }
        unset($row);

        return view('cases.find-preview', [
            'rows' => $rows,
            'level' => $validated['level'],
            'portal' => self::PORTALS[$validated['level']],
            'defaultCourt' => $validated['court_name'] ?? null,
            'retry' => $retry?->id,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retry' => ['nullable', 'integer', 'min:1'],
            'cases' => ['required', 'array', 'min:1', 'max:300'],
            'cases.*.selected' => ['nullable', 'boolean'],
            'cases.*.case_title' => ['required', 'string', 'max:255'],
            'cases.*.case_number' => ['required', 'string', 'max:100'],
            'cases.*.case_type' => ['nullable', 'string', 'max:100'],
            'cases.*.cnr_number' => ['nullable', 'string', 'regex:/^[A-Za-z]{4}\d{12}$/'],
            'cases.*.court_name' => ['nullable', 'string', 'max:255'],
            'cases.*.petitioner' => ['nullable', 'string', 'max:200'],
            'cases.*.respondent' => ['nullable', 'string', 'max:200'],
        ]);

        $retry = CourtRecordImport::retryParent($validated['retry'] ?? null, 'website');
        $selected = collect($validated['cases'])->filter(fn (array $case): bool => (bool) ($case['selected'] ?? false))->values();

        if ($selected->isEmpty()) {
            return redirect()->route('cases.find')->withErrors(['cases' => 'Tick at least one case to add.']);
        }

        $missingCourt = $selected->filter(fn (array $case): bool => blank($case['court_name'] ?? null));

        if ($missingCourt->isNotEmpty()) {
            $this->recordPastedAttempt('failed', $retry?->id, ['reason' => 'A selected case did not have a court.']);

            return redirect()->route('cases.find', $retry ? ['method' => 'website', 'retry' => $retry->id] : [])->withErrors(['cases' => 'Enter the court for every case you tick ('.$missingCourt->pluck('case_number')->implode(', ').').']);
        }

        $added = DB::transaction(function () use ($selected, $request, $retry): int {
            User::query()->whereKey($request->user()->workspaceId())->lockForUpdate()->firstOrFail();
            $added = 0;
            $entries = [];

            foreach ($selected as $row) {
                $cnr = isset($row['cnr_number']) ? strtoupper($row['cnr_number']) : null;
                $alreadyAdded = LegalCase::query()
                    ->where(fn ($query) => $query
                        ->when($cnr, fn ($query) => $query->orWhere('cnr_number', $cnr))
                        ->orWhere(fn ($query) => $query->where('case_number', $row['case_number'])->where('court_name', $row['court_name'])))
                    ->exists();

                if ($alreadyAdded) {
                    $entries[] = ['reference' => $row['case_number'], 'outcome' => 'Skipped - already in your cases'];

                    continue;
                }

                LegalCase::create([
                    'case_title' => $row['case_title'],
                    'case_number' => $row['case_number'],
                    'cnr_number' => $cnr,
                    'case_type' => $row['case_type'] ?? null,
                    'court_name' => $row['court_name'],
                    'case_status' => 'pending',
                    'source_type' => 'ecourts_guided_import',
                    'source_reference' => 'Pasted from an eCourts advocate search',
                ]);
                $added++;
                $entries[] = ['reference' => $row['case_number'], 'outcome' => 'Added'];
            }

            $this->recordPastedAttempt('imported', $retry?->id, [
                'counts' => ['added' => $added, 'updated' => 0, 'skipped' => $selected->count() - $added],
                'entries' => $entries,
            ]);

            return $added;
        });

        $skipped = $selected->count() - $added;

        return redirect()->route('cases.index')->with('success', trim(
            ($added === 1 ? '1 case added.' : "{$added} cases added.")
            .($skipped ? ' '.($skipped === 1 ? '1 was' : "{$skipped} were").' already in your cases.' : '')
            .($added ? ' Open a case and use Import official record to add its hearings and full details.' : '')
        ));
    }

    private function recordPastedAttempt(string $status, ?int $retry, array $outcome): void
    {
        DB::transaction(function () use ($status, $retry, $outcome): void {
            $attempt = CourtRecordImport::create([
                'source_name' => 'Pasted eCourts results', 'raw_content' => '', 'import_status' => $status,
                'parsed_data' => array_filter(['method' => 'website', 'retry_of' => $retry]) + $outcome,
            ]);
            $attempt->auditRetryOutcome(auth()->id());
        });
    }
}
