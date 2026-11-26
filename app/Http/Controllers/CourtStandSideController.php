<?php

namespace App\Http\Controllers;

use App\Models\CourtStandSide;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Records, once per court, which side the petitioner's counsel stands on; every case in
 * that court then shows where we stand.
 */
class CourtStandSideController extends Controller
{
    public function store(Request $request, LegalCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'petitioner_side' => ['required', Rule::in(array_keys(CourtStandSide::SIDES))],
        ]);

        abort_if(blank($case->court_name), 422, 'Add the court name to this case first.');

        CourtStandSide::saveForCourt($case, $validated['petitioner_side']);

        return redirect()->to(route('cases.show', $case).'#case-overview')
            ->with('success', 'Saved for every case in '.$case->court_name.'.');
    }
}
