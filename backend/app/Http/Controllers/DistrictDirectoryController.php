<?php

namespace App\Http\Controllers;

use App\Models\JudicialDistrict;

class DistrictDirectoryController extends Controller
{
    public function show(int $districtId)
    {
        $district = JudicialDistrict::query()
            ->where('id', $districtId)
            ->where('is_active', true)
            ->with(['state', 'establishments.court'])
            ->firstOrFail();

        return view('court-directory.district', [
            'district' => $district,
            'establishments' => $district->establishments,
        ]);
    }
}