<?php

namespace App\Http\Controllers;

use App\Models\CourtComplex;
use App\Models\HighCourt;
use App\Models\State;
use Illuminate\Http\Request;

class CourtDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $stateId = $request->input('state_id');
        $courtType = $request->input('court_type');
        $search = trim((string) $request->input('q', ''));

        $availableStates = State::orderBy('name')->get();

        // 1. Constitutional Courts (Supreme Court & High Courts)
        $hcQuery = HighCourt::query()->with('state')->where('is_active', true);
        if ($stateId) {
            $hcQuery->where(function ($q) use ($stateId) {
                $q->where('state_id', $stateId)->orWhere('court_type', 'Supreme Court');
            });
        }
        if ($courtType && in_array($courtType, ['Supreme Court', 'High Court', 'High Court Bench'])) {
            $hcQuery->where('court_type', $courtType);
        }
        if ($search !== '') {
            $hcQuery->where(function ($q) use ($search) {
                $q->where('court_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('jurisdiction_states', 'like', "%{$search}%");
            });
        }
        $highCourts = $hcQuery->orderByRaw("CASE WHEN court_type = 'Supreme Court' THEN 0 ELSE 1 END")
                              ->orderBy('court_name')
                              ->get();

        // 2. District & Subordinate Court Complexes
        $ccQuery = CourtComplex::query()->with(['state', 'highCourt'])->where('is_active', true);
        if ($stateId) {
            $ccQuery->where('state_id', $stateId);
        }
        if ($search !== '') {
            $ccQuery->where(function ($q) use ($search) {
                $q->where('complex_name', 'like', "%{$search}%")
                  ->orWhere('judicial_district_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('pincode', 'like', "%{$search}%");
            });
        }
        $courtComplexes = ($courtType && $courtType !== 'District Court')
            ? collect()
            : $ccQuery->orderBy('complex_name')->paginate(12)->withQueryString();

        return view('modules.court-directory', compact(
            'availableStates',
            'highCourts',
            'courtComplexes',
            'search',
            'courtType'
        ));
    }
}