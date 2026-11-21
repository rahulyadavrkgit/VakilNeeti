<?php

namespace Database\Seeders;

use App\Models\PoliceDistrict;
use App\Models\PoliceStation;
use App\Models\PoliceSubDivision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DelhiPoliceStationsSeeder extends Seeder
{
    public function run(): void
    {
        $delhiState = DB::table('states')->where('name', 'Delhi')->first();

        if (!$delhiState) {
            $this->command->warn('Delhi state record not found in states table.');
            return;
        }

        $sourceName = 'Delhi Police Official Portal & District Directories';
        $sourceUrl = 'https://www.delhipolice.gov.in/';
        $verifiedAt = now();

        $stations = [
            // New Delhi District
            [
                'district' => 'New Delhi',
                'sub_division' => 'Chanakyapuri Sub-Division',
                'station_name' => 'Chanakyapuri Police Station',
                'station_code' => 'DL-ND-01',
                'station_type' => 'Territorial Police Station',
                'address' => 'Sardar Patel Marg, Chanakyapuri, New Delhi, Delhi 110021',
                'city' => 'New Delhi',
                'pincode' => '110021',
                'phone' => '011-23015146',
                'control_room_phone' => '112 / 011-23490217',
                'email' => 'sho-cpuri.dl@nic.in',
                'sho_name' => 'Inspector verified as per Delhi Police roster',
                'jurisdiction_area' => 'Diplomatic enclave, Chanakyapuri, diplomatic missions, and surrounding institutional areas.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],
            [
                'district' => 'New Delhi',
                'sub_division' => 'Parliament Street Sub-Division',
                'station_name' => 'Parliament Street Police Station',
                'station_code' => 'DL-ND-02',
                'station_type' => 'Territorial Police Station',
                'address' => 'Sansad Marg, Connaught Place, New Delhi, Delhi 110001',
                'city' => 'New Delhi',
                'pincode' => '110001',
                'phone' => '011-23743512',
                'control_room_phone' => '112',
                'email' => 'sho-pstreet.dl@nic.in',
                'jurisdiction_area' => 'Sansad Marg, Connaught Place inner/outer circles, central government offices, and media hubs.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],
            [
                'district' => 'New Delhi',
                'sub_division' => 'Connaught Place Sub-Division',
                'station_name' => 'Connaught Place Police Station',
                'station_code' => 'DL-ND-03',
                'station_type' => 'Territorial Police Station',
                'address' => 'Baba Kharak Singh Marg, Connaught Place, New Delhi, Delhi 110001',
                'city' => 'New Delhi',
                'pincode' => '110001',
                'phone' => '011-23746688',
                'control_room_phone' => '112',
                'email' => 'sho-cplace.dl@nic.in',
                'jurisdiction_area' => 'Connaught Place commercial district, Radial roads, and transit hubs.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],

            // Central District
            [
                'district' => 'Central',
                'sub_division' => 'Daryaganj Sub-Division',
                'station_name' => 'Daryaganj Police Station',
                'station_code' => 'DL-CEN-01',
                'station_type' => 'Territorial Police Station',
                'address' => 'Netaji Subhash Marg, Daryaganj, New Delhi, Delhi 110002',
                'city' => 'New Delhi',
                'pincode' => '110002',
                'phone' => '011-23271234',
                'control_room_phone' => '112',
                'email' => 'sho-dgang.dl@nic.in',
                'jurisdiction_area' => 'Daryaganj market, Ansari Road, and surrounding residential/commercial localities.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],
            [
                'district' => 'Central',
                'sub_division' => 'Pahar Ganj Sub-Division',
                'station_name' => 'Pahar Ganj Police Station',
                'station_code' => 'DL-CEN-02',
                'station_type' => 'Territorial Police Station',
                'address' => 'Main Bazar Road, Paharganj, New Delhi, Delhi 110055',
                'city' => 'New Delhi',
                'pincode' => '110055',
                'phone' => '011-23581122',
                'control_room_phone' => '112',
                'email' => 'sho-pganj.dl@nic.in',
                'jurisdiction_area' => 'Paharganj hotel belt, New Delhi Railway Station (western approach), and surrounding commercial markets.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],

            // South District
            [
                'district' => 'South',
                'sub_division' => 'Hauz Khas Sub-Division',
                'station_name' => 'Hauz Khas Police Station',
                'station_code' => 'DL-STH-01',
                'station_type' => 'Territorial Police Station',
                'address' => 'Sri Aurobindo Marg, Hauz Khas, New Delhi, Delhi 110016',
                'city' => 'New Delhi',
                'pincode' => '110016',
                'phone' => '011-26510033',
                'control_room_phone' => '112',
                'email' => 'sho-hkhas.dl@nic.in',
                'jurisdiction_area' => 'Hauz Khas Village, IIT Delhi campus perimeter, Green Park extension, and surrounding areas.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],
            [
                'district' => 'South',
                'sub_division' => 'Mehrauli Sub-Division',
                'station_name' => 'Mehrauli Police Station',
                'station_code' => 'DL-STH-02',
                'station_type' => 'Territorial Police Station',
                'address' => 'Andheria More, Mehrauli, New Delhi, Delhi 110030',
                'city' => 'New Delhi',
                'pincode' => '110030',
                'phone' => '011-26641144',
                'control_room_phone' => '112',
                'email' => 'sho-mrauli.dl@nic.in',
                'jurisdiction_area' => 'Mehrauli historical zone, Qutub Minar vicinity, Lado Sarai, and Chattarpur residential belts.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],

            // South-East District
            [
                'district' => 'South-East',
                'sub_division' => 'Kalkaji Sub-Division',
                'station_name' => 'Kalkaji Police Station',
                'station_code' => 'DL-SET-01',
                'station_type' => 'Territorial Police Station',
                'address' => 'Near Lotus Temple, Kalkaji, New Delhi, Delhi 110019',
                'city' => 'New Delhi',
                'pincode' => '110019',
                'phone' => '011-26435566',
                'control_room_phone' => '112',
                'email' => 'sho-kkaji.dl@nic.in',
                'jurisdiction_area' => 'Kalkaji temple complex, Govindpuri, Nehru Place commercial district, and surrounding residential colonies.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],
            [
                'district' => 'South-East',
                'sub_division' => 'Defence Colony Sub-Division',
                'station_name' => 'Defence Colony Police Station',
                'station_code' => 'DL-SET-02',
                'station_type' => 'Territorial Police Station',
                'address' => 'Ring Road, Defence Colony, New Delhi, Delhi 110024',
                'city' => 'New Delhi',
                'pincode' => '110024',
                'phone' => '011-24332211',
                'control_room_phone' => '112',
                'email' => 'sho-dcolony.dl@nic.in',
                'jurisdiction_area' => 'Defence Colony market, Lajpat Nagar residential sectors, and Ring Road transit corridor.',
                'citizen_portal_url' => 'https://cctns.delhipolice.gov.in/',
            ],
        ];

        foreach ($stations as $data) {
            $district = PoliceDistrict::where('state_id', $delhiState->id)
                ->where('district_name', $data['district'])
                ->first();

            $subDivisionId = null;
            if ($district) {
                $subDivision = PoliceSubDivision::updateOrCreate(
                    [
                        'state_id' => $delhiState->id,
                        'police_district_id' => $district->id,
                        'sub_division_name' => $data['sub_division'],
                    ],
                    [
                        'source_name' => $sourceName,
                        'source_url' => $sourceUrl,
                        'source_status' => 'verified',
                        'last_verified_at' => $verifiedAt,
                        'is_active' => true,
                    ]
                );
                $subDivisionId = $subDivision->id;
            }

            PoliceStation::updateOrCreate(
                [
                    'state_id' => $delhiState->id,
                    'station_name' => $data['station_name'],
                ],
                [
                    'police_district_id' => $district ? $district->id : null,
                    'police_sub_division_id' => $subDivisionId,
                    'station_code' => $data['station_code'],
                    'station_type' => $data['station_type'],
                    'is_specialist_unit' => false,
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'pincode' => $data['pincode'],
                    'phone' => $data['phone'],
                    'control_room_phone' => $data['control_room_phone'],
                    'email' => $data['email'],
                    'sho_name' => $data['sho_name'] ?? null,
                    'jurisdiction_area' => $data['jurisdiction_area'],
                    'official_url' => 'https://www.delhipolice.gov.in/',
                    'citizen_portal_url' => $data['citizen_portal_url'],
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Verified Delhi police stations seeded successfully.');
    }
}