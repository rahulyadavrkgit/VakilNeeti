<?php

namespace Database\Seeders;

use App\Models\BarAssociation;
use App\Models\CourtComplex;
use App\Models\HighCourt;
use App\Models\PoliceDistrict;
use App\Models\PoliceStation;
use App\Models\PoliceSubDivision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UttarPradeshJurisdictionSeeder extends Seeder
{
    public function run(): void
    {
        $upState = DB::table('states')->where('name', 'Uttar Pradesh')->first();
        if (!$upState) {
            $this->command->warn('Uttar Pradesh state not found in database.');
            return;
        }

        $verifiedAt = now();
        $sourceName = 'UP Police Portal & eCourts Uttar Pradesh';

        // 1. Fetch Controlling High Courts for UP
        $allahabadHC = HighCourt::where('court_name', 'High Court of Judicature at Allahabad')->first();
        $lucknowBench = HighCourt::where('court_name', 'Like', '%Lucknow Bench%')->first();

        // 2. Seed / Resolve District Court Complexes & Bar Associations
        
        // --- A. Gautam Buddh Nagar (Noida / Greater Noida) ---
        $gbnComplex = CourtComplex::updateOrCreate(
            ['state_id' => $upState->id, 'complex_name' => 'District & Sessions Court, Gautam Buddh Nagar'],
            [
                'high_court_id' => $allahabadHC?->id,
                'judicial_district_name' => 'Gautam Buddh Nagar',
                'complex_type' => 'District & Sessions Court',
                'address' => 'Surajpur, Greater Noida, Gautam Buddh Nagar, Uttar Pradesh 201306',
                'city' => 'Greater Noida',
                'pincode' => '201306',
                'official_url' => 'https://gautambudhnagar.dcourts.gov.in/',
                'ecourts_url' => 'https://services.ecourts.gov.in/',
                'source_name' => $sourceName,
                'source_url' => 'https://gautambudhnagar.dcourts.gov.in/',
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        BarAssociation::updateOrCreate(
            ['association_name' => 'District Bar Association, Gautam Buddh Nagar'],
            [
                'state_id' => $upState->id,
                'court_complex_id' => $gbnComplex->id,
                'court_type' => 'District Court',
                'office_address' => 'District Court Complex, Surajpur, Greater Noida, UP 201306',
                'official_url' => 'https://gautambudhnagar.dcourts.gov.in/',
                'source_name' => $sourceName,
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        // --- B. Prayagraj (Allahabad) ---
        $prayagrajComplex = CourtComplex::updateOrCreate(
            ['state_id' => $upState->id, 'complex_name' => 'District & Sessions Court, Prayagraj'],
            [
                'high_court_id' => $allahabadHC?->id,
                'judicial_district_name' => 'Prayagraj',
                'complex_type' => 'District & Sessions Court',
                'address' => 'Civil Lines, Prayagraj, Uttar Pradesh 211001',
                'city' => 'Prayagraj',
                'pincode' => '211001',
                'official_url' => 'https://prayagraj.dcourts.gov.in/',
                'ecourts_url' => 'https://services.ecourts.gov.in/',
                'source_name' => $sourceName,
                'source_url' => 'https://prayagraj.dcourts.gov.in/',
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        BarAssociation::updateOrCreate(
            ['association_name' => 'High Court Bar Association, Prayagraj'],
            [
                'state_id' => $upState->id,
                'court_complex_id' => $prayagrajComplex->id,
                'court_type' => 'High Court',
                'office_address' => 'High Court Campus, Prayagraj, UP 211001',
                'official_url' => 'https://allahabadhighcourt.in/',
                'source_name' => $sourceName,
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        // --- C. Lucknow ---
        $lucknowComplex = CourtComplex::updateOrCreate(
            ['state_id' => $upState->id, 'complex_name' => 'District & Sessions Court, Lucknow'],
            [
                'high_court_id' => $lucknowBench?->id ?? $allahabadHC?->id,
                'judicial_district_name' => 'Lucknow',
                'complex_type' => 'District & Sessions Court',
                'address' => 'Kutchery Road, Kaiserbagh, Lucknow, Uttar Pradesh 226001',
                'city' => 'Lucknow',
                'pincode' => '226001',
                'official_url' => 'https://lucknow.dcourts.gov.in/',
                'ecourts_url' => 'https://services.ecourts.gov.in/',
                'source_name' => $sourceName,
                'source_url' => 'https://lucknow.dcourts.gov.in/',
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        BarAssociation::updateOrCreate(
            ['association_name' => 'Awadh Bar Association, Lucknow Bench High Court'],
            [
                'state_id' => $upState->id,
                'court_complex_id' => $lucknowComplex->id,
                'court_type' => 'High Court',
                'office_address' => 'High Court Lucknow Bench, Gomti Nagar, Lucknow, UP 226010',
                'official_url' => 'https://allahabadhighcourt.in/',
                'source_name' => $sourceName,
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );


        // 3. Seed Police Districts & Stations with Cross-Links

        // --- Gautam Buddh Nagar Police Commissionerate ---
        $gbnPoliceDist = PoliceDistrict::updateOrCreate(
            ['state_id' => $upState->id, 'district_name' => 'Gautam Buddh Nagar'],
            [
                'range_name' => 'Meerut Zone / Commissionerate',
                'headquarters_city' => 'Noida',
                'source_name' => 'UP Police Portal',
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        $noidaSubDiv = PoliceSubDivision::updateOrCreate(
            ['police_district_id' => $gbnPoliceDist->id, 'sub_division_name' => 'Noida Sector 20'],
            ['circle_officer_designation' => 'CO Noida', 'is_active' => true]
        );

        $gbnStations = [
            [
                'name' => 'Sector 20 Police Station Noida',
                'code' => 'GBN-S20',
                'sub_div' => $noidaSubDiv->id,
                'complex' => $gbnComplex,
                'address' => 'Sector 20, Noida, Gautam Buddh Nagar, UP 201301',
                'notes' => 'Remand & Sessions Trial Jurisdiction: District & Sessions Court, Gautam Buddh Nagar (Surajpur)',
            ],
            [
                'name' => 'Phase 3 Police Station Noida',
                'code' => 'GBN-P3',
                'sub_div' => $noidaSubDiv->id,
                'complex' => $gbnComplex,
                'address' => 'Sector 71, Noida, Gautam Buddh Nagar, UP 201301',
                'notes' => 'Remand & Sessions Trial Jurisdiction: District & Sessions Court, Gautam Buddh Nagar (Surajpur)',
            ],
            [
                'name' => 'Knowledge Park Police Station Greater Noida',
                'code' => 'GBN-KP',
                'sub_div' => $noidaSubDiv->id,
                'complex' => $gbnComplex,
                'address' => 'Knowledge Park III, Greater Noida, UP 201310',
                'notes' => 'Remand & Sessions Trial Jurisdiction: District & Sessions Court, Gautam Buddh Nagar (Surajpur)',
            ],
        ];

        foreach ($gbnStations as $st) {
            PoliceStation::updateOrCreate(
                ['state_id' => $upState->id, 'station_name' => $st['name']],
                [
                    'police_district_id' => $gbnPoliceDist->id,
                    'police_sub_division_id' => $st['sub_div'],
                    'court_complex_id' => $st['complex']->id,
                    'station_code' => $st['code'],
                    'station_type' => 'Police Station',
                    'address' => $st['address'],
                    'city' => 'Noida / Greater Noida',
                    'pincode' => '201301',
                    'remand_court_notes' => $st['notes'],
                    'source_name' => $sourceName,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }


        // --- Prayagraj Police District ---
        $prayagrajPoliceDist = PoliceDistrict::updateOrCreate(
            ['state_id' => $upState->id, 'district_name' => 'Prayagraj Police District'],
            [
                'range_name' => 'Prayagraj Zone',
                'headquarters_city' => 'Prayagraj',
                'source_name' => 'UP Police Portal',
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        $civilLinesSubDiv = PoliceSubDivision::updateOrCreate(
            ['police_district_id' => $prayagrajPoliceDist->id, 'sub_division_name' => 'Civil Lines Prayagraj'],
            ['circle_officer_designation' => 'CO Civil Lines', 'is_active' => true]
        );

        PoliceStation::updateOrCreate(
            ['state_id' => $upState->id, 'station_name' => 'Civil Lines Police Station Prayagraj'],
            [
                'police_district_id' => $prayagrajPoliceDist->id,
                'police_sub_division_id' => $civilLinesSubDiv->id,
                'court_complex_id' => $prayagrajComplex->id,
                'station_code' => 'PRY-CL',
                'station_type' => 'Police Station',
                'address' => 'Civil Lines, Prayagraj, Uttar Pradesh 211001',
                'city' => 'Prayagraj',
                'pincode' => '211001',
                'remand_court_notes' => 'Remand & Trial Jurisdiction: District & Sessions Court, Prayagraj & Allahabad High Court',
                'source_name' => $sourceName,
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );


        // --- Lucknow Police Commissionerate ---
        $lucknowPoliceDist = PoliceDistrict::updateOrCreate(
            ['state_id' => $upState->id, 'district_name' => 'Lucknow Police Commissionerate'],
            [
                'range_name' => 'Lucknow Zone',
                'headquarters_city' => 'Lucknow',
                'source_name' => 'UP Police Portal',
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        $hazratganjSubDiv = PoliceSubDivision::updateOrCreate(
            ['police_district_id' => $lucknowPoliceDist->id, 'sub_division_name' => 'Hazratganj Circle'],
            ['circle_officer_designation' => 'CO Hazratganj', 'is_active' => true]
        );

        PoliceStation::updateOrCreate(
            ['state_id' => $upState->id, 'station_name' => 'Hazratganj Police Station Lucknow'],
            [
                'police_district_id' => $lucknowPoliceDist->id,
                'police_sub_division_id' => $hazratganjSubDiv->id,
                'court_complex_id' => $lucknowComplex->id,
                'station_code' => 'LKO-HZG',
                'station_type' => 'Police Station',
                'address' => 'Hazratganj, Lucknow, Uttar Pradesh 226001',
                'city' => 'Lucknow',
                'pincode' => '226001',
                'remand_court_notes' => 'Remand & Trial Jurisdiction: District & Sessions Court, Lucknow & Lucknow Bench High Court',
                'source_name' => $sourceName,
                'source_status' => 'verified',
                'last_verified_at' => $verifiedAt,
                'is_active' => true,
            ]
        );

        $this->command->info('Uttar Pradesh jurisdiction, police stations, court complexes, and bar associations seeded successfully.');
    }
}