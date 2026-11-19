<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtEstablishment;
use App\Models\CourtType;
use App\Models\JudicialDistrict;
use App\Models\State;
use Illuminate\Http\Request;

class CourtCatalogueController extends Controller
{
    public function index()
    {
        $states = State::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $courtTypes = CourtType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('court-catalogue.index', compact(
            'states',
            'courtTypes'
        ));
    }

    // Load courts after court type and state selection.
    public function courts(Request $request)
    {
        $validated = $request->validate([
            'court_type_id' => ['required', 'exists:court_types,id'],
            'state_id' => ['nullable', 'exists:states,id'],
        ]);

        $courtType = CourtType::findOrFail(
            $validated['court_type_id']
        );

        if ($courtType->requires_state && empty($validated['state_id'])) {
            return response()->json([
                'message' => 'Please select a state first.',
                'data' => [],
            ], 422);
        }

        $query = Court::query()
            ->where('court_type_id', $courtType->id)
            ->where('is_active', true);

        if (!empty($validated['state_id'])) {
            $query->where('state_id', $validated['state_id']);
        }

        $courts = $query
            ->orderBy('court_name')
            ->get(['id', 'court_name', 'court_code']);

        return response()->json([
            'data' => $courts->map(fn ($court) => [
                'id' => $court->id,
                'label' => $court->court_name,
                'code' => $court->court_code,
            ]),
        ]);
    }

    // Load judicial districts for the selected state and court.
    public function districts(Request $request)
    {
        $validated = $request->validate([
            'state_id' => ['required', 'exists:states,id'],
            'court_id' => ['nullable', 'exists:courts,id'],
        ]);

        $query = JudicialDistrict::where(
            'state_id',
            $validated['state_id']
        )->where('is_active', true);

        if (!empty($validated['court_id'])) {
            $courtId = $validated['court_id'];

            $query->where(function ($q) use ($courtId) {
                $q->whereNull('court_id')
                    ->orWhere('court_id', $courtId);
            });
        }

        $districts = $query
            ->orderBy('district_name')
            ->get(['id', 'district_name', 'district_code']);

        return response()->json([
            'data' => $districts->map(fn ($district) => [
                'id' => $district->id,
                'label' => $district->district_name,
                'code' => $district->district_code,
            ]),
        ]);
    }

    // Load physical complexes or benches for the selected court.
    public function establishments(Request $request)
    {
        $validated = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'district_id' => [
                'nullable',
                'exists:judicial_districts,id',
            ],
        ]);

        $query = CourtEstablishment::with('court')
            ->where('court_id', $validated['court_id'])
            ->where('is_active', true);

        if (!empty($validated['district_id'])) {
            $query->whereHas(
                'judicialDistricts',
                function ($q) use ($validated) {
                    $q->where(
                        'judicial_districts.id',
                        $validated['district_id']
                    );
                }
            );
        }

        $establishments = $query
            ->orderBy('establishment_name')
            ->get();

        return response()->json([
            'data' => $establishments->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->establishment_name,
                'type' => $item->establishment_type,
                'city' => $item->city,
                'district' => $item->district,
            ]),
        ]);
    }
}
