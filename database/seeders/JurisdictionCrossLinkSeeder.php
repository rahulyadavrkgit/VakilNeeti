<?php

namespace Database\Seeders;

use App\Models\BarAssociation;
use App\Models\CourtComplex;
use App\Models\PoliceStation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JurisdictionCrossLinkSeeder extends Seeder
{
    public function run(): void
    {
        $delhiState = DB::table('states')->where('name', 'Delhi')->first();
        if (!$delhiState) {
            $this->command->warn('Delhi state not found.');
            return;
        }

        $verifiedAt = now();

        // Fetch Delhi Court Complexes
        $patialaHouse = CourtComplex::where('complex_name', 'Patiala House Court Complex')->first();
        $tisHazari    = CourtComplex::where('complex_name', 'Tis Hazari Court Complex')->first();
        $saket        = CourtComplex::where('complex_name', 'Saket Court Complex')->first();

        // 1. Ensure Court-Affiliated Bar Associations exist and link to Court Complexes
        if ($patialaHouse) {
            BarAssociation::updateOrCreate(
                ['association_name' => 'New Delhi Bar Association (NDBA)'],
                [
                    'state_id' => $delhiState->id,
                    'court_complex_id' => $patialaHouse->id,
                    'court_type' => 'District Court',
                    'office_address' => 'Patiala House Courts Complex, India Gate Circle, New Delhi 110001',
                    'contact_phone' => '011-23381483',
                    'official_url' => 'https://newdelhi.dcourts.gov.in/',
                    'source_name' => 'Delhi District Courts Official Portal',
                    'source_url' => 'https://newdelhi.dcourts.gov.in/',
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        if ($tisHazari) {
            BarAssociation::updateOrCreate(
                ['association_name' => 'Delhi Bar Association (DBA)'],
                [
                    'state_id' => $delhiState->id,
                    'court_complex_id' => $tisHazari->id,
                    'court_type' => 'District Court',
                    'office_address' => 'Tis Hazari Courts Complex, New Delhi 110054',
                    'official_url' => 'https://delhidistrictcourts.nic.in/',
                    'source_name' => 'Delhi District Courts Official Portal',
                    'source_url' => 'https://delhidistrictcourts.nic.in/',
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        if ($saket) {
            BarAssociation::updateOrCreate(
                ['association_name' => 'Saket Bar Association'],
                [
                    'state_id' => $delhiState->id,
                    'court_complex_id' => $saket->id,
                    'court_type' => 'District Court',
                    'office_address' => 'Saket Courts Complex, Sector 6, Pushp Vihar, New Delhi 110017',
                    'official_url' => 'https://saket.dcourts.gov.in/',
                    'source_name' => 'Delhi District Courts Official Portal',
                    'source_url' => 'https://saket.dcourts.gov.in/',
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        // 2. Map Delhi Police Stations to Judicial Remand & Trial Court Complexes
        $stationMappings = [
            // New Delhi Police District -> Patiala House Court Complex
            'Chanakyapuri Police Station' => [
                'complex' => $patialaHouse,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Patiala House Courts (New Delhi Judicial District)',
            ],
            'Parliament Street Police Station' => [
                'complex' => $patialaHouse,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Patiala House Courts (New Delhi Judicial District)',
            ],
            'Connaught Place Police Station' => [
                'complex' => $patialaHouse,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Patiala House Courts (New Delhi Judicial District)',
            ],

            // Central Police District -> Tis Hazari Court Complex
            'Daryaganj Police Station' => [
                'complex' => $tisHazari,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Tis Hazari Courts (Central Judicial District)',
            ],
            'Pahar Ganj Police Station' => [
                'complex' => $tisHazari,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Tis Hazari Courts (Central Judicial District)',
            ],

            // South & South-East Police Districts -> Saket Court Complex
            'Hauz Khas Police Station' => [
                'complex' => $saket,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Saket Courts (South Judicial District)',
            ],
            'Mehrauli Police Station' => [
                'complex' => $saket,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Saket Courts (South Judicial District)',
            ],
            'Kalkaji Police Station' => [
                'complex' => $saket,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Saket Courts (South-East Judicial District)',
            ],
            'Defence Colony Police Station' => [
                'complex' => $saket,
                'notes' => 'JMFC / CJM Remand & Sessions Trial Jurisdiction: Saket Courts (South-East Judicial District)',
            ],
        ];

        foreach ($stationMappings as $stationName => $map) {
            if (!$map['complex']) continue;

            PoliceStation::where('state_id', $delhiState->id)
                ->where('station_name', $stationName)
                ->update([
                    'court_complex_id' => $map['complex']->id,
                    'remand_court_notes' => $map['notes'],
                ]);
        }

        $this->command->info('Police Station <-> Court Complex <-> Bar Association cross-links seeded successfully.');
    }
}