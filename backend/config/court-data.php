<?php

use App\Services\CourtData\MockCourtDataProvider;
use App\Services\CourtData\SupremeCourtWebsiteProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Development Provider
    |--------------------------------------------------------------------------
    */

    'default_provider' => env(
        'COURT_DATA_DEFAULT_PROVIDER',
        'mock'
    ),

    /*
    |--------------------------------------------------------------------------
    | Registered Court Data Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'ecourts_district_services' => \App\Providers\EcourtsDistrictDirectoryProvider::class,

        'mock' => MockCourtDataProvider::class,

        'supreme_court_website' =>
            SupremeCourtWebsiteProvider::class,

        // Future official API provider, subject to approval:
        // 'ecourts' => EcourtsApiProvider::class,

        // Future court website connectors:
        // 'high_court_website' => HighCourtWebsiteProvider::class,
        // 'district_court_website' => DistrictCourtWebsiteProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Synchronisation Defaults
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'timeout' => 30,
        'retry_attempts' => 2,
        'retry_delay_seconds' => 1,
        'maximum_records_per_batch' => 100,
    ],

];
