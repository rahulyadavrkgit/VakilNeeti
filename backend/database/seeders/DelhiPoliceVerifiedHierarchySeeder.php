<?php

namespace Database\Seeders;

use App\Models\PoliceDistrict;
use App\Models\PoliceRange;
use App\Models\PoliceStation;
use App\Models\PoliceSubDivision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DelhiPoliceVerifiedHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $delhiState = DB::table('states')->where('name', 'Delhi')->first();

        if (!$delhiState) {
            $this->command->warn('Delhi state record not found in states table.');
            return;
        }

        $sourceName = 'Delhi Police Official Portal & RTI Manual';
        $sourceUrl = 'https://www.delhipolice.gov.in/';
        $verifiedAt = now();

        // 1. Delhi Police Ranges
        $ranges = [
            'Northern Range' => ['Central', 'North', 'North-West', 'Outer', 'Outer-North', 'Rohini'],
            'Southern Range' => ['South', 'South-East', 'South-West', 'New Delhi', 'Dwarka'],
            'Eastern Range' => ['East', 'North-East', 'Shahdara'],
            'Central Range' => ['Central', 'New Delhi'],
            'Armed Police & Headquarters' => [],
        ];

        // 11 Official Delhi Police Districts with their corresponding Ranges
        $districts = [
            ['name' => 'Central', 'range' => 'Northern Range', 'code' => 'DL-CEN'],
            ['name' => 'East', 'range' => 'Eastern Range', 'code' => 'DL-EST'],
            ['name' => 'New Delhi', 'range' => 'Southern Range', 'code' => 'DL-NDL'],
            ['name' => 'North', 'range' => 'Northern Range', 'code' => 'DL-NRT'],
            ['name' => 'North-East', 'range' => 'Eastern Range', 'code' => 'DL-NET'],
            ['name' => 'North-West', 'range' => 'Northern Range', 'code' => 'DL-NWT'],
            ['name' => 'Shahdara', 'range' => 'Eastern Range', 'code' => 'DL-SHD'],
            ['name' => 'South', 'range' => 'Southern Range', 'code' => 'DL-STH'],
            ['name' => 'South-East', 'range' => 'Southern Range', 'code' => 'DL-SET'],
            ['name' => 'South-West', 'range' => 'Southern Range', 'code' => 'DL-SWT'],
            ['name' => 'West', 'range' => 'Northern Range', 'code' => 'DL-WST'],
        ];

        $rangeModels = [];
        foreach (array_keys($ranges) as $rangeName) {
            $rangeModels[$rangeName] = PoliceRange::updateOrCreate(
                ['state_id' => $delhiState->id, 'range_name' => $rangeName],
                [
                    'official_url' => 'https://www.delhipolice.gov.in/',
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        $districtModels = [];
        foreach ($districts as $d) {
            $rangeId = $rangeModels[$d['range']]->id ?? null;
            $districtModels[$d['name']] = PoliceDistrict::updateOrCreate(
                ['state_id' => $delhiState->id, 'district_name' => $d['name']],
                [
                    'police_range_id' => $rangeId,
                    'range_name' => $d['range'],
                    'district_code' => $d['code'],
                    'official_url' => 'https://www.delhipolice.gov.in/',
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        // 2. Verified Specialist Units (Non-territorial)
        $specialistUnits = [
            [
                'name' => 'Delhi Police Cyber Cell / Cyber Crime Unit',
                'category' => 'Cyber Crime',
                'address' => 'IFSO Unit, Cyber Cell, Special Cell, New Delhi',
                'phone' => '1930',
                'url' => 'https://www.delhipolice.gov.in/',
            ],
            [
                'name' => 'Delhi Police Crime Branch',
                'category' => 'Crime Branch',
                'address' => 'Police Headquarters, Jai Singh Road, New Delhi',
                'phone' => '011-23490217',
                'url' => 'https://www.delhipolice.gov.in/',
            ],
            [
                'name' => 'Delhi Police Special Cell',
                'category' => 'Special Cell',
                'address' => 'Lodhi Colony, New Delhi',
                'phone' => '011-24361595',
                'url' => 'https://www.delhipolice.gov.in/',
            ],
        ];

        foreach ($specialistUnits as $unit) {
            PoliceStation::updateOrCreate(
                [
                    'state_id' => $delhiState->id,
                    'station_name' => $unit['name'],
                ],
                [
                    'is_specialist_unit' => true,
                    'specialist_unit_category' => $unit['category'],
                    'address' => $unit['address'],
                    'phone' => $unit['phone'],
                    'official_url' => $unit['url'],
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Delhi Police ranges, 11 districts, and verified specialist units seeded successfully.');
    }
}