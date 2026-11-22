<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One search box for the signed-in user's own cases, clients and invoices.
 */
class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        // Treat % and _ typed by the person as plain characters, not wildcards.
        $like = '%'.addcslashes($query, '\\%_').'%';
        $results = ['cases' => collect(), 'clients' => collect(), 'invoices' => collect()];

        if (mb_strlen($query) >= 2) {
            $results['cases'] = LegalCase::query()
                ->where(fn ($q) => $q->where('case_title', 'like', $like)
                    ->orWhere('case_number', 'like', $like)
                    ->orWhere('cnr_number', 'like', $like)
                    ->orWhere('file_number', 'like', $like)
                    ->orWhere('court_name', 'like', $like))
                ->latest()
                ->limit(10)
                ->get();

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
