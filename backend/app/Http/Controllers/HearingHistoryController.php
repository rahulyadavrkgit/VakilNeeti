<?php

namespace App\Http\Controllers;

use App\Models\CourtHearing;
use Illuminate\Http\Request;

class HearingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = CourtHearing::with('legalCase')
            ->whereNotNull('hearing_date')
            ->whereHas('legalCase');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->whereHas('legalCase', function ($caseQuery) use ($search) {
                $caseQuery->where('case_title', 'like', "%{$search}%")
                    ->orWhere('case_number', 'like', "%{$search}%")
                    ->orWhere('cnr_number', 'like', "%{$search}%");
            });
        }

        $hearings = $query
            ->orderByDesc('hearing_date')
            ->orderByDesc('hearing_time')
            ->paginate(20)
            ->withQueryString();

        return view('cases.hearing-history', compact('hearings'));
    }
}
