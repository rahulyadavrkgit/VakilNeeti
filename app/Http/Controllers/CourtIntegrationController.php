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
            return redirect()->route('courts.ecourts-services');
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
}
