<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CourtBench;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Services\SiteDestinations;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One search box for the signed-in user's own cases, clients and invoices, plus the official court lists
 * (judge, room). Cases are also found by court, establishment, judge and room.
 */
class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        // Treat % and _ typed by the person as plain characters, not wildcards.
        $like = '%'.addcslashes($query, '\\%_').'%';
        $results = ['cases' => collect(), 'court_rooms' => collect(), 'clients' => collect(), 'invoices' => collect(), 'places' => app(SiteDestinations::class)->matching($query, 10)];

        if (mb_strlen($query) >= 2) {
            // Cases: also by court, establishment, judge and room (including the official court lists).
            $results['cases'] = LegalCase::query()
                ->search($query)
                ->latest()
                ->limit(10)
                ->get();

            // Court rooms in the official lists: a judge's name or a room number.
            $results['court_rooms'] = CourtBench::query()
                ->get()
                ->filter(fn ($bench) => str_contains(mb_strtolower(collect($bench->judgeList())->pluck('name')->implode(' ').' '.$bench->court_room.' '.$bench->section), mb_strtolower($query)))
                ->take(8)
                ->values();

            $results['clients'] = Client::query()
                ->where(fn ($q) => $q->where('display_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('organisation_name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->latest()
                ->limit(10)
                ->get();

            $results['invoices'] = Invoice::query()
                ->with('client')
                ->where('invoice_number', 'like', $like)
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('search.index', [
            'query' => $query,
            'results' => $results,
            'total' => collect($results)->sum(fn ($items) => $items->count()),
        ]);
    }
}
