<?php

namespace App\Console\Commands;

use App\Models\CourtDataSource;
use Illuminate\Console\Command;

class RegisterECourtsSource extends Command
{
    protected $signature = 'ecourts:register-source';

    protected $description = 'Register the official eCourts District Services portal';

    public function handle(): int
    {
        CourtDataSource::updateOrCreate(
            ['provider_key' => 'ecourts_district_services'],
            [
                'provider_name' => 'eCourts Services - District and Subordinate Courts',
                'source_type' => 'official_government_portal',
                'base_url' => 'https://services.ecourts.gov.in/ecourtindia_v6/',
                'terms_url' => null,
                'attribution_text' => 'Source: eCourts Services, Government of India.',
                'is_enabled' => true,
                'metadata' => [
                    'integration_mode' => 'portal_assisted',
                    'live_api_available' => false,
                    'captcha_handling' => 'User completes official portal verification when required.',
                    'coverage' => 'District and subordinate courts',
                    'integration_status' => 'portal_assisted_not_live_api',
                ],
            ]
        );

        $this->info('Official eCourts District Services source registered successfully.');
        $this->line('Integration mode: Portal-assisted.');
        $this->line('Live API connection: Not configured.');

        return self::SUCCESS;
    }
}