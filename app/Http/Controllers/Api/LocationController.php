<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\JudicialDistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * India's location lists for pickers. They change rarely, so the server keeps them for a day
 * and the browser may reuse them for a day too.
 */
class LocationController extends Controller
{
    private const CACHE_SECONDS = 86400;

    /**
     * Cities and towns of one district, for location dropdowns.
     */
    public function cities(int $districtId): JsonResponse
    {
        $payload = Cache::remember("locations:cities:{$districtId}", self::CACHE_SECONDS, function () use ($districtId): array {
            $district = JudicialDistrict::query()->where('is_active', true)->findOrFail($districtId);
            $cities = City::query()
                ->where('judicial_district_id', $district->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'pincode']);

            return [
                'success' => true,
                'district' => ['id' => $district->id, 'name' => $district->district_name],
                'data' => $cities->toArray(),
                'count' => $cities->count(),
            ];
        });

        return response()->json($payload)->header('Cache-Control', 'public, max-age='.self::CACHE_SECONDS);
    }

    /**
     * Judicial district names of one State/UT by its letters ("UP", "DL"), for the court picker on cases.
     */
    public function courtDistricts(string $stateCode): JsonResponse
    {
        $stateCode = strtoupper($stateCode);
        $names = Cache::remember("locations:court-districts:{$stateCode}", self::CACHE_SECONDS, fn (): array => DB::table('judicial_districts')
            ->join('states', 'states.id', '=', 'judicial_districts.state_id')
            ->where('states.code', 'IN-'.$stateCode)
            ->whereNull('judicial_districts.deleted_at')
            ->where('judicial_districts.is_active', true)
            ->orderBy('judicial_districts.district_name')
            ->pluck('judicial_districts.district_name')
            ->unique()
            ->values()
            ->all());

        return response()->json(['data' => $names])->header('Cache-Control', 'public, max-age='.self::CACHE_SECONDS);
    }
}
