<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Services\CourtBenchMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

        return view('sheets.next-date', [
            'case' => $case,
            'cases' => $case ? collect() : $this->openCases(),
        ]);
    }

    public function task(Request $request): View
    {
        return view('sheets.task', [
            'caseId' => $request->integer('case') ?: null,
            'cases' => $this->openCases(),
        ]);
    }

    public function client(): View
    {
        return view('sheets.client');
    }

    public function case(): View
    {
        return view('sheets.case', ['sides' => LegalCase::SIDES]);
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
