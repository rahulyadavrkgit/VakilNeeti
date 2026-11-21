<?php

namespace App\Http\Controllers;

use App\Models\CourtDataSource;
use Illuminate\Support\Facades\DB;

class CourtIntegrationController extends Controller
{
    public function index()
    {
        $source = CourtDataSource::where(
            'provider_key',
            'ecourts_district_services'
        )->firstOrFail();

        $syncRuns = $source->syncRuns()->latest()->limit(10)->get();

        $recordCount = DB::table('court_data_records')
            ->where('provider_key', 'ecourts_district_services')
            ->count();

        return view('court-integrations.ecourts', [
            'source' => $source,
            'syncRuns' => $syncRuns,
            'recordCount' => $recordCount,
        ]);
    }

    public function search()
    {
        $source = CourtDataSource::where(
            'provider_key',
            'ecourts_district_services'
        )->firstOrFail();

        return view('court-integrations.search', [
            'source' => $source,
        ]);
    }
}