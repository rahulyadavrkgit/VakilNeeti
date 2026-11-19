<?php

namespace Database\Seeders;

use App\Models\CourtDataSource;
use Illuminate\Database\Seeder;

class CourtDataSourceSeeder extends Seeder
{
    public function run(): void
    {
        CourtDataSource::updateOrCreate(
            [
                'provider_key' => 'mock',
            ],
            [
                'provider_name' =>
                    'VakilNeeti Mock Development Provider',
                'source_type' => 'mock',
                'base_url' => null,
                'terms_url' => null,
                'attribution_text' =>
                    'Development-only provider. Does not contain official court data.',
                'is_enabled' => true,
                'metadata' => [
                    'environment' => 'development',
                    'contains_official_data' => false,
                ],
            ]
        );

        CourtDataSource::updateOrCreate(
            [
                'provider_key' => 'supreme_court_website',
            ],
            [
                'provider_name' =>
                    'Supreme Court of India Official Website',
                'source_type' => 'official_website',
                'base_url' => 'https://www.sci.gov.in/index/',
                'terms_url' => null,
                'attribution_text' =>
                    'Source: Supreme Court of India official website. '
                    . 'Information is reproduced for reference with its original source link. '
                    . 'Users should verify the information against the official website.',
                'is_enabled' => true,
                'metadata' => [
                    'court' => 'Supreme Court of India',
                    'country' => 'India',
                    'website' => 'https://www.sci.gov.in/',
                    'source_page' => 'https://www.sci.gov.in/index/',
                    'retrieval_method' => 'public_html',
                    'supported_entities' => [
                        'cause_list_index',
                    ],
                    'data_classification' =>
                        'published_listing_information',
                    'is_live_data' => false,
                    'requires_captcha' => false,
                    'manual_sync_only' => true,
                ],
            ]
        );
    }
}
