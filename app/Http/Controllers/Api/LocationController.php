<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\JudicialDistrict;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * Cities and towns of one district, for location dropdowns.
     */
    public function cities(int $districtId): JsonResponse
    {
        $district = JudicialDistrict::query()
            ->where('is_active', true)
            ->findOrFail($districtId);

        $cities = City::query()
            ->where('judicial_district_id', $district->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'pincode']);

        return response()->json([
            'success' => true,
            'district' => ['id' => $district->id, 'name' => $district->district_name],
            'data' => $cities,
            'count' => $cities->count(),
        ]);
    }
}
