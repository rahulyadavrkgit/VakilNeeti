<?php

namespace App\Http\Controllers;

use App\Models\BarAssociation;
use App\Models\BarCouncil;
use App\Models\State;
use Illuminate\Http\Request;

class BarDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $stateId = $request->input('state_id');
        $courtType = $request->input('court_type');
        $search = trim((string) $request->input('q', ''));

        // Available States for filter dropdown
        $availableStates = State::orderBy('name')->get();

        // 1. Query Bar Councils
        $councilsQuery = BarCouncil::query()->with('state')->where('is_active', true);
        if ($stateId) {
            $councilsQuery->where(function($q) use ($stateId) {
                $q->where('state_id', $stateId)->orWhere('jurisdiction_type', 'national');
            });
        }
        if ($search !== '') {
            $councilsQuery->where(function($q) use ($search) {
                $q->where('council_name', 'like', "%{$search}%")
                  ->orWhere('office_address', 'like', "%{$search}%");
            });
        }
        $barCouncils = $councilsQuery->orderBy('council_name')->get();

        // 2. Query Bar Associations
        $associationsQuery = BarAssociation::query()->with('state')->where('is_active', true);
        if ($stateId) {
            $associationsQuery->where('state_id', $stateId);
        }
        if ($courtType) {
            $associationsQuery->where('court_type', $courtType);
        }
        if ($search !== '') {
            $associationsQuery->where(function($q) use ($search) {
                $q->where('association_name', 'like', "%{$search}%")
                  ->orWhere('office_address', 'like', "%{$search}%")
                  ->orWhere('president_name', 'like', "%{$search}%")
                  ->orWhere('secretary_name', 'like', "%{$search}%");
            });
        }
        $barAssociations = $associationsQuery->orderBy('association_name')->paginate(12)->withQueryString();

        $selectedState = $stateId ? State::find($stateId) : null;

        return view('modules.bar-directory', compact(
            'availableStates',
            'barCouncils',
            'barAssociations',
            'selectedState',
            'search',
            'courtType'
        ));
    }
}