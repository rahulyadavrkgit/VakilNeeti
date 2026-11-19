<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CourtDataSource;

class SupremeCourtDataSourceSeeder extends Seeder
{
    public function run(): void
    {
        CourtDataSource::updateOrCreate(
            [
                'provider_key' => 'supreme_court_india',
            ],
            [
                'provider_name' => 'Supreme Court of India',
                'source_type' => 'official_website',
                'base_url' => 'https://www.sci.gov.in/',
                'terms_url' => 'https://www.sci.gov.in/',
                'attribution_text' => 'Source: Official website of the Supreme Court of India.',
                'is_enabled' => true,

                'metadata' => [
                    'country' => 'India',
                    'court_code' => 'SCI',
                    'website' => 'https://www.sci.gov.in/',
                    'cause_list_url' => 'https://www.sci.gov.in/cause-list/',
                    'index_url' => 'https://www.sci.gov.in/index/',
                    'integration_method' => 'public_website_and_direct_pdf',
                    'captcha_required_for_search_form' => true,
                    'automated_captcha_bypass' => false,
                    'supports_direct_pdf_download' => true,
                ],
            ]
        );
    }
}
