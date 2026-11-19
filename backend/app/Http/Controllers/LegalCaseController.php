<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use Illuminate\Http\Request;

class LegalCaseController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'hearing_filter' => [
                'nullable',
                'in:all,today,tomorrow,next7,next30,past,no_upcoming'
            ],
        ]);

        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $nextSeven = now()->addDays(7)->toDateString();
        $nextThirty = now()->addDays(30)->toDateString();

        $hearingFilter = $validated['hearing_filter'] ?? 'all';

        $query = LegalCase::with([
                'parties',
                'hearings' => function ($hearingQuery) {
                    $hearingQuery
                        ->orderBy('next_hearing_date')
                        ->orderBy('hearing_time');
                },
            ])
            ->withCount('hearings');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchType = $request->input('search_type', 'all');

            $query->where(function ($q) use ($search, $searchType) {
                if ($searchType === 'cnr') {
                    $q->where('cnr_number', 'like', "%{$search}%");
                } elseif ($searchType === 'case_number') {
                    $q->where('case_number', 'like', "%{$search}%");
                } elseif ($searchType === 'party_name') {
                    $q->whereHas('parties', function ($partyQuery) use ($search) {
                        $partyQuery->where('party_name', 'like', "%{$search}%");
                    });
                } elseif ($searchType === 'case_title') {
                    $q->where('case_title', 'like', "%{$search}%");
                } else {
                    $q->where('case_title', 'like', "%{$search}%")
                        ->orWhere('case_number', 'like', "%{$search}%")
                        ->orWhere('cnr_number', 'like', "%{$search}%")
                        ->orWhere('court_name', 'like', "%{$search}%")
                        ->orWhereHas('parties', function ($partyQuery) use ($search) {
                            $partyQuery->where('party_name', 'like', "%{$search}%");
                        });
                }
            });
        }

        if ($request->filled('case_status')) {
            $query->where('case_status', $request->case_status);
        }

        // Filter cases using their recorded next hearing dates.
        if ($hearingFilter === 'today') {
            $query->whereHas('hearings', function ($q) use ($today) {
                $q->whereDate('next_hearing_date', $today);
            });
        } elseif ($hearingFilter === 'tomorrow') {
            $query->whereHas('hearings', function ($q) use ($tomorrow) {
                $q->whereDate('next_hearing_date', $tomorrow);
            });
        } elseif ($hearingFilter === 'next7') {
            $query->whereHas('hearings', function ($q) use ($today, $nextSeven) {
                $q->whereDate('next_hearing_date', '>', $today)
                    ->whereDate('next_hearing_date', '<=', $nextSeven);
            });
        } elseif ($hearingFilter === 'next30') {
            $query->whereHas('hearings', function ($q) use ($today, $nextThirty) {
                $q->whereDate('next_hearing_date', '>=', $today)
                    ->whereDate('next_hearing_date', '<=', $nextThirty);
            });
        } elseif ($hearingFilter === 'past') {
            $query->whereHas('hearings', function ($q) use ($today) {
                $q->whereNotNull('next_hearing_date')
                    ->whereDate('next_hearing_date', '<', $today);
            })->whereDoesntHave('hearings', function ($q) use ($today) {
                $q->whereNotNull('next_hearing_date')
                    ->whereDate('next_hearing_date', '>=', $today);
            });
        } elseif ($hearingFilter === 'no_upcoming') {
            $query->whereDoesntHave('hearings', function ($q) use ($today) {
                $q->whereNotNull('next_hearing_date')
                    ->whereDate('next_hearing_date', '>=', $today);
            });
        }

        $cases = $query
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('cases.index', compact('cases', 'hearingFilter'));
    }

    public function create()
    {
        return view('cases.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'case_title' => ['required', 'string', 'max:255'],
            'case_number' => ['nullable', 'string', 'max:255'],
            'cnr_number' => ['nullable', 'string', 'max:255'],
            'case_type' => ['nullable', 'string', 'max:255'],
            'court_name' => ['required', 'string', 'max:255'],
            'court_complex' => ['nullable', 'string', 'max:255'],
            'judge_name' => ['nullable', 'string', 'max:255'],
            'filing_number' => ['nullable', 'string', 'max:255'],
            'filing_date' => ['nullable', 'date'],
            'case_status' => ['required', 'string', 'max:255'],
            'case_description' => ['nullable', 'string'],
        ]);

        $validated['source_type'] = 'manual';

        $case = LegalCase::create($validated);

        return redirect()
            ->route('cases.show', $case)
            ->with('success', 'Case registered successfully.');
    }

    public function show(LegalCase $case)
    {
        $case->load([
            'parties.client',
            'hearings' => function ($query) {
                $query->orderByDesc('hearing_date')
                    ->orderByDesc('next_hearing_date');
            },
        ]);

        return view('cases.show', compact('case'));
    }
}