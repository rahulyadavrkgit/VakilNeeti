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
        )->first();

        // Until the eCourts source is set up, send people to the official court search instead of a 404.
        if (! $source) {
            return redirect()->route('court-integrations.ecourts.search');
        }

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

    /**
     * Links to the official court search portals. Works even before the eCourts data source is seeded.
     */
    public function search()
    {
        return view('court-integrations.search', [
            'source' => CourtDataSource::where('provider_key', 'ecourts_district_services')->first(),
        ]);
    }
}
