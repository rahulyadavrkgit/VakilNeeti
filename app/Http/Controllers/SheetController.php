<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\Invoice;
use App\Models\JudicialDistrict;
use App\Models\LegalCase;
use App\Models\State;
use App\Services\CourtBenchMatcher;
use App\Services\CourtIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pop-up sheets: small pieces of a page loaded into the site-wide sheet (resources/js/sheet.js),
 * so everyday actions happen without leaving the page. Each sheet's form saves through the
 * normal save action of that record, with the same checks as the full page.
 */
class SheetController extends Controller
{
    /**
     * Add the next date: the case is filled in when known; otherwise pick one of the open cases.
     */
    public function nextDate(Request $request): View
    {
        $case = $request->filled('case') ? LegalCase::query()->findOrFail($request->integer('case')) : null;
        // A day picked on the calendar comes in as ?date=; only a day from today on can be a next date.
        $date = rescue(fn () => Carbon::createFromFormat('Y-m-d', (string) $request->query('date'))->startOfDay(), null, false);

        return view('sheets.next-date', [
            'case' => $case,
            'cases' => $case ? collect() : $this->openCases(),
            'date' => $date && ($date->isToday() || $date->isFuture()) ? $date->toDateString() : null,
        ]);
    }

    public function task(Request $request): View
    {
        return view('sheets.task', [
            'caseId' => $request->integer('case') ?: null,
            'cases' => $this->openCases(),
        ]);
    }

    public function client(Request $request): View
    {
        return view('sheets.client', ['isCompany' => $request->query('type') === 'company']);
    }

    public function case(): View
    {
        return view('sheets.case', ['sides' => LegalCase::SIDES]);
    }

    /**
     * "Add case": search any court (Supreme Court, every High Court, every district court) and go on to import from
     * it, or look up a CNR, import an eCourts app list or calendar, or type the case in.
     */
    public function addCase(): View
    {
        $courts = Cache::remember('add-case:courts', now()->addDay(), function (): array {
            $states = State::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code']);
            $codes = $states->mapWithKeys(fn (State $state): array => [$state->id => Str::after((string) $state->code, 'IN-')]);

            $highCourts = $states
                ->map(fn (State $state): array => [
                    'level' => 'high_court',
                    'name' => CourtIdentity::fullName('high', $codes[$state->id]) ?? 'High Court of '.$state->name,
                    'hint' => $state->name,
                ])
                ->groupBy('name')
                ->map(fn (Collection $group): array => $group->first() + ['hint' => $group->pluck('hint')->implode(', ')]);

            $districtCourts = JudicialDistrict::query()->where('is_active', true)->orderBy('district_name')->get(['state_id', 'district_name'])
                ->map(fn (JudicialDistrict $district): array => [
                    'level' => 'district',
                    'name' => 'District Court '.$district->district_name,
                    'hint' => $states->firstWhere('id', $district->state_id)?->name,
                ]);

            return collect([['level' => 'supreme_court', 'name' => 'Supreme Court of India', 'hint' => 'New Delhi']])
                ->concat($highCourts->sortBy('name')->values())
                ->concat($districtCourts)
                ->values()
                ->all();
        });

        // The courts this workspace already uses (Supreme Court, a High Court, a district), offered first.
        $yourCourts = LegalCase::query()->whereIn('court_level', ['supreme', 'high', 'district'])
            ->selectRaw('court_level, state_code, district, count(*) as uses')->groupBy('court_level', 'state_code', 'district')
            ->orderByDesc('uses')->limit(5)->get()
            ->map(fn (LegalCase $case): array => [
                'level' => match ($case->court_level) {
                    'supreme' => 'supreme_court', 'high' => 'high_court', default => 'district'
                },
                'name' => match ($case->court_level) {
                    'supreme' => 'Supreme Court of India',
                    'high' => CourtIdentity::fullName('high', $case->state_code) ?? 'High Court',
                    default => $case->district ? 'District Court '.$case->district : (CourtIdentity::label('district', $case->state_code, null) ?? 'District court'),
                },
                'hint' => $case->uses.' '.Str::plural('case', (int) $case->uses),
            ]);

        return view('sheets.add-case', ['courts' => $courts, 'yourCourts' => $yourCourts]);
    }

    /**
     * How a case can be looked up on eCourts, and at which court levels each way exists.
     *
     * @var array<string, array{label: string, icon: string, levels: list<string>}>
     */
    public const SEARCH_METHODS = [
        'party' => ['label' => 'Party name', 'icon' => 'people', 'levels' => ['district', 'high_court', 'supreme_court']],
        'case_number' => ['label' => 'Case number', 'icon' => 'list', 'levels' => ['district', 'high_court', 'supreme_court']],
        'filing_number' => ['label' => 'Filing number', 'icon' => 'document', 'levels' => ['district', 'high_court']],
        'diary_number' => ['label' => 'Diary number', 'icon' => 'document', 'levels' => ['supreme_court']],
        'fir' => ['label' => 'FIR number', 'icon' => 'shield', 'levels' => ['district', 'high_court']],
        'barcode' => ['label' => 'Bar code', 'icon' => 'pass', 'levels' => ['district', 'high_court']],
        'advocate' => ['label' => 'Advocate name', 'icon' => 'user', 'levels' => ['district', 'high_court', 'supreme_court']],
    ];

    /**
     * Add case, step 2: the ways to find a case in the chosen court.
     */
    public function addCaseMethod(Request $request): View
    {
        $court = $this->addCaseCourt($request);

        return view('sheets.add-case-method', $court + [
            'methods' => array_filter(self::SEARCH_METHODS, fn (array $method): bool => in_array($court['level'], $method['levels'], true)),
        ]);
    }

    /**
     * Add case, step 3: the details for the chosen way (party name and year, case type, number and year…).
     */
    public function addCaseSearch(Request $request, string $method): View
    {
        $court = $this->addCaseCourt($request, $method);

        return view('sheets.add-case-search', $court + [
            'method' => $method,
            'methodLabel' => self::SEARCH_METHODS[$method]['label'],
            'fields' => $this->searchFields($method, $request),
        ]);
    }

    /**
     * Add case, step 4: search eCourts with those details, then paste the results to import them.
     * (Until an eCourts data service is connected, the search itself runs on the official eCourts site.)
     */
    public function addCaseContinue(Request $request, string $method): View
    {
        $court = $this->addCaseCourt($request, $method);
        $fields = $this->searchFields($method, $request);
        $entries = $request->validate(collect($fields)->mapWithKeys(fn (array $field, string $name): array => [
            $name => [$field['required'] ? 'required' : 'nullable', 'string', 'max:120'],
        ])->all());

        return view('sheets.add-case-continue', $court + [
            'method' => $method,
            'methodLabel' => self::SEARCH_METHODS[$method]['label'],
            'portal' => CaseFinderController::PORTALS[$court['level']],
            'entries' => collect($fields)
                ->filter(fn (array $field, string $name): bool => filled($entries[$name] ?? null))
                ->map(fn (array $field, string $name): array => ['label' => $field['label'], 'value' => $entries[$name]])
                ->values(),
        ]);
    }

    /**
     * The court carried through the Add case steps, and the chosen way if it exists at that court's level.
     *
     * @return array{level: string, court: string}
     */
    private function addCaseCourt(Request $request, ?string $method = null): array
    {
        $data = $request->validate([
            'level' => ['required', Rule::in(array_keys(CaseFinderController::PORTALS))],
            'court' => ['required', 'string', 'max:255'],
        ]);
        abort_if($method !== null && ! in_array($data['level'], self::SEARCH_METHODS[$method]['levels'] ?? [], true), 404);

        return ['level' => $data['level'], 'court' => $data['court']];
    }

    /**
     * The details each way of searching asks for, with the advocate's own name and bar code filled in.
     *
     * @return array<string, array{label: string, type: string, placeholder: string, required: bool, value: ?string}>
     */
    private function searchFields(string $method, Request $request): array
    {
        $user = $request->user();
        $field = fn (string $label, string $placeholder = '', bool $required = true, string $type = 'text', ?string $value = null): array => compact('label', 'type', 'placeholder', 'required', 'value');
        $year = fn (string $label, bool $required = true): array => $field($label, 'Select year', $required, 'year');

        $fields = match ($method) {
            'party' => ['party_name' => $field('Petitioner / respondent name', 'Petitioner or respondent'), 'year' => $year('Year', false)],
            'case_number' => ['case_type' => $field('Case type', 'e.g. CS, HMA, CRL.A., W.P.(C)'), 'case_number' => $field('Case number', 'e.g. 2424'), 'year' => $year('Case year')],
            'filing_number' => ['filing_number' => $field('Filing number', 'Filing number'), 'year' => $year('Filing year')],
            'diary_number' => ['diary_number' => $field('Diary number', 'Diary number'), 'year' => $year('Diary year')],
            'fir' => ['police_station' => $field('Police station', 'Police station'), 'fir_number' => $field('FIR number', 'FIR number'), 'year' => $year('FIR year')],
            'barcode' => ['bar_code' => $field('Bar code number', 'Your Bar Council enrolment, e.g. D/1234/2015', true, 'text', $user?->advocateProfile?->bar_council_registration_number)],
            'advocate' => ['advocate_name' => $field('Advocate name', 'Advocate name', true, 'text', $user?->name), 'year' => $year('Case year', false)],
            default => abort(404),
        };

        foreach ($fields as $name => &$details) {
            $details['value'] = $request->query($name, $details['value']);
        }
        unset($details);

        return $fields;
    }

    /**
     * A case at a glance: parties and our side, next date, court, room, judge and VC, client contact.
     */
    public function caseView(LegalCase $case): View
    {
        $case->load(['parties.client', 'hearings' => fn ($hearings) => $hearings->orderByDesc('hearing_date')]);

        $today = today();
        $next = $case->hearings
            ->flatMap(fn (CourtHearing $hearing) => collect([$hearing->hearing_date, $hearing->next_hearing_date])
                ->filter(fn ($date) => $date !== null && $date->gte($today))
                ->map(fn ($date) => ['date' => $date, 'hearing' => $hearing]))
            ->sortBy(fn (array $item) => $item['date']->toDateString())
            ->first();
        $last = $case->hearings->first(fn (CourtHearing $hearing) => $hearing->hearing_date !== null && $hearing->hearing_date->lt($today));

        return view('sheets.case-view', [
            'case' => $case,
            'next' => $next,
            'last' => $last,
        ]);
    }

    /**
     * A client's contact card: call, WhatsApp, email, and a ready hearing update for each open case.
     */
    public function clientCard(Client $client): View
    {
        $cases = LegalCase::query()
            ->whereNull('closed_at')
            ->whereHas('parties', fn ($parties) => $parties->where('client_id', $client->id))
            ->with('hearings')
            ->get();

        return view('sheets.client-card', [
            'client' => $client,
            'cases' => $cases,
            'advocateName' => auth()->user()->name,
        ]);
    }

    /**
     * The court room from the official list, for a room or judge chip: judge, room, VC link, meeting ID, email.
     */
    public function court(Request $request, CourtBenchMatcher $matcher): View
    {
        $bench = $request->filled('bench')
            ? CourtBench::query()->find($request->integer('bench'))
            : $matcher->find($request->string('designation')->toString() ?: $request->string('court')->toString(), $request->string('complex')->toString() ?: null, $request->string('judge')->toString() ?: null);

        return view('sheets.court', [
            'bench' => $bench,
            'court' => $request->string('court')->toString(),
            'room' => $request->string('room')->toString(),
            'judge' => $request->string('judge')->toString(),
            'vcLink' => $request->string('vc')->toString(),
            'courtName' => $bench ? (CourtBench::courts()[$bench->court_key]['court'] ?? null) : null,
            'listUrl' => $bench ? $this->listUrl($bench->court_key) : null,
        ]);
    }

    /**
     * "Record payment" without an invoice in hand: the unpaid invoices (optionally one client's) to pick from.
     */
    public function paymentPick(Request $request): View
    {
        $invoices = Invoice::query()
            ->with(['client', 'payments'])
            ->whereNotIn('status', ['paid', 'cancelled', 'written_off'])
            ->whereNull('written_off_at')
            ->when($request->integer('client'), fn ($query, int $client) => $query->where('client_id', $client))
            ->latest('invoice_date')
            ->limit(100)
            ->get()
            ->filter(fn (Invoice $invoice): bool => $invoice->balanceDue() > 0)
            ->values();

        return view('sheets.payment-pick', ['invoices' => $invoices, 'clientId' => $request->integer('client') ?: null]);
    }

    public function payment(Invoice $invoice): View
    {
        $invoice->load(['client', 'payments']);

        return view('sheets.payment', [
            'invoice' => $invoice,
            'balance' => $invoice->balanceDue(),
        ]);
    }

    /**
     * Search as you type: the person's cases, clients and invoices, and court rooms from the official lists.
     */
    public function search(Request $request): View
    {
        $query = Str::of((string) $request->query('q'))->squish()->limit(100, '')->toString();
        $results = ['cases' => collect(), 'clients' => collect(), 'rooms' => collect()];

        if (mb_strlen($query) >= 2) {
            $like = '%'.$query.'%';
            $results['cases'] = LegalCase::query()->search($query)->orderBy('case_title')->limit(8)->get();
            $results['clients'] = Client::query()
                ->where(fn ($clients) => $clients->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                    ->orWhere('organisation_name', 'like', $like)->orWhere('mobile', 'like', $like))
                ->limit(5)
                ->get();
            // Matched in the database (lower-cased, since the judges column is case-sensitive on MySQL).
            $needle = '%'.Str::lower($query).'%';
            $results['rooms'] = CourtBench::query()
                ->where(fn ($benches) => $benches->whereRaw('LOWER(CAST(judges AS CHAR)) LIKE ?', [$needle])->orWhereRaw('LOWER(court_room) LIKE ?', [$needle]))
                ->limit(5)
                ->get();
        }

        return view('sheets.search-results', ['query' => $query, 'results' => $results]);
    }

    /**
     * @return Collection<int, LegalCase>
     */
    private function openCases()
    {
        return LegalCase::query()
            ->whereNull('closed_at')
            ->orderBy('case_title')
            ->limit(500)
            ->get(['id', 'case_title', 'case_number']);
    }

    private function listUrl(string $courtKey): ?string
    {
        return match (true) {
            $courtKey === 'supreme-court' => route('courts.supreme-court.court-list'),
            $courtKey === 'delhi-high-court' => route('courts.delhi-high-court.judges'),
            str_starts_with($courtKey, 'delhi-district-') => route('courts.delhi-district-courts.judges', Str::after($courtKey, 'delhi-district-')),
            default => null,
        };
    }
}
