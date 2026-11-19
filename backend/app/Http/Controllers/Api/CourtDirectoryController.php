<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\JudicialDistrict;
use Illuminate\Http\JsonResponse;

class CourtDirectoryController extends Controller
{
    public function states(): JsonResponse
    {
        $states = State::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'country_code',
                'is_union_territory',
            ]);

        return response()->json([
            'success' => true,
            'data' => $states,
            'count' => $states->count(),
        ]);
    }

    public function districts(int $stateId): JsonResponse
    {
        $state = State::query()
            ->where('is_active', true)
            ->findOrFail($stateId);

        $districts = JudicialDistrict::query()
            ->where('state_id', $state->id)
            ->where('is_active', true)
            ->orderBy('district_name')
            ->get([
                'id',
                'state_id',
                'district_name',
                'district_code',
                'source_url',
                'source_status',
            ]);

        return response()->json([
            'success' => true,
            'state' => [
                'id' => $state->id,
                'name' => $state->name,
                'code' => $state->code,
            ],
            'data' => $districts,
            'count' => $districts->count(),
        ]);
    }

    public function establishments(int $districtId): JsonResponse
    {
        $district = JudicialDistrict::query()
            ->where('is_active', true)
            ->findOrFail($districtId);

        $establishments = $district->establishments()
            ->where('court_establishments.is_active', true)
            ->with('court:id,court_name,court_code')
            ->orderBy('court_establishments.establishment_name')
            ->get([
                'court_establishments.id',
                'court_establishments.court_id',
                'court_establishments.establishment_name',
                'court_establishments.establishment_code',
                'court_establishments.establishment_type',
                'court_establishments.official_website',
                'court_establishments.source_url',
                'court_establishments.source_status',
            ]);

        return response()->json([
            'success' => true,
            'district' => [
                'id' => $district->id,
                'name' => $district->district_name,
                'code' => $district->district_code,
            ],
            'data' => $establishments,
            'count' => $establishments->count(),
            'official_portal' => 'https://services.ecourts.gov.in/ecourtindia_v6/',
        ]);
    }
}