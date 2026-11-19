<?php

namespace Database\Seeders;

use App\Models\CourtComplex;
use App\Models\HighCourt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerifiedJudicialDirectorySeeder extends Seeder
{
    public function run(): void
    {
        $verifiedAt = now();
        $sourceName = 'Department of Justice (DoJ) & eCourts Services Portal';
        $sourceUrl = 'https://doj.gov.in/';

        // 1. Seed Constitutional Courts (Supreme Court & Major High Courts)
        $constitutionalCourts = [
            [
                'state_name' => 'Delhi',
                'court_name' => 'Supreme Court of India',
                'court_type' => 'Supreme Court',
                'seat_type' => 'Principal Seat',
                'city' => 'New Delhi',
                'jurisdiction_states' => 'All India',
                'address' => 'Tilak Marg, New Delhi, Delhi 110001',
                'official_url' => 'https://www.sci.gov.in/',
                'ecourts_url' => 'https://services.ecourts.gov.in/',
            ],
            [
                'state_name' => 'Delhi',
                'court_name' => 'High Court of Delhi',
                'court_type' => 'High Court',
                'seat_type' => 'Principal Seat',
                'city' => 'New Delhi',
                'jurisdiction_states' => 'National Capital Territory of Delhi',
                'address' => 'Sher Shah Road, New Delhi, Delhi 110503',
                'official_url' => 'https://delhihighcourt.nic.in/',
                'ecourts_url' => 'https://hcservices.ecourts.gov.in/',
            ],
            [
                'state_name' => 'Uttar Pradesh',
                'court_name' => 'High Court of Judicature at Allahabad',
                'court_type' => 'High Court',
                'seat_type' => 'Principal Seat',
                'city' => 'Prayagraj',
                'jurisdiction_states' => 'Uttar Pradesh',
                'address' => 'Nyaya Marg, Cantonment, Prayagraj, Uttar Pradesh 211017',
                'official_url' => 'https://www.allahabadhighcourt.in/',
                'ecourts_url' => 'https://hcservices.ecourts.gov.in/',
            ],
            [
                'state_name' => 'Uttar Pradesh',
                'court_name' => 'High Court of Judicature at Allahabad — Lucknow Bench',
                'court_type' => 'High Court Bench',
                'seat_type' => 'Permanent Bench',
                'city' => 'Lucknow',
                'jurisdiction_states' => 'Awadh Region, Uttar Pradesh',
                'address' => 'Vibhuti Khand, Gomti Nagar, Lucknow, Uttar Pradesh 226010',
                'official_url' => 'https://www.allahabadhighcourt.in/',
                'ecourts_url' => 'https://hcservices.ecourts.gov.in/',
            ],
            [
                'state_name' => 'Maharashtra',
                'court_name' => 'High Court of Bombay',
                'court_type' => 'High Court',
                'seat_type' => 'Principal Seat',
                'city' => 'Mumbai',
                'jurisdiction_states' => 'Maharashtra, Goa, Dadra and Nagar Haveli and Daman and Diu',
                'address' => 'Dr. Kane Road, Fort, Mumbai, Maharashtra 400032',
                'official_url' => 'https://bombayhighcourt.nic.in/',
                'ecourts_url' => 'https://hcservices.ecourts.gov.in/',
            ],
            [
                'state_name' => 'Karnataka',
                'court_name' => 'High Court of Karnataka',
                'court_type' => 'High Court',
                'seat_type' => 'Principal Seat',
                'city' => 'Bengaluru',
                'jurisdiction_states' => 'Karnataka',
                'address' => 'Dr. B.R. Ambedkar Veedhi, Bengaluru, Karnataka 560001',
                'official_url' => 'https://karnatakajudiciary.kar.nic.in/',
                'ecourts_url' => 'https://hcservices.ecourts.gov.in/',
            ],
            [
                'state_name' => 'Tamil Nadu',
                'court_name' => 'High Court of Judicature at Madras',
                'court_type' => 'High Court',
                'seat_type' => 'Principal Seat',
                'city' => 'Chennai',
                'jurisdiction_states' => 'Tamil Nadu, Puducherry',
                'address' => 'High Court Road, Parry\'s Corner, George Town, Chennai, Tamil Nadu 600104',
                'official_url' => 'https://hcmadras.tn.gov.in/',
                'ecourts_url' => 'https://hcservices.ecourts.gov.in/',
            ],
            [
                'state_name' => 'West Bengal',
                'court_name' => 'High Court at Calcutta',
                'court_type' => 'High Court',
                'seat_type' => 'Principal Seat',
                'city' => 'Kolkata',
                'jurisdiction_states' => 'West Bengal, Andaman and Nicobar Islands',
                'address' => '3, Esplanade Row West, B.B.D. Bagh, Kolkata, West Bengal 700001',
                'official_url' => 'https://www.calcuttahighcourt.gov.in/',
                'ecourts_url' => 'https://hcservices.ecourts.gov.in/',
            ],
        ];

        $hcMap = [];
        foreach ($constitutionalCourts as $c) {
            $state = DB::table('states')->where('name', $c['state_name'])->first();
            $hc = HighCourt::updateOrCreate(
                ['court_name' => $c['court_name']],
                [
                    'state_id' => $state ? $state->id : null,
                    'court_type' => $c['court_type'],
                    'seat_type' => $c['seat_type'],
                    'city' => $c['city'],
                    'jurisdiction_states' => $c['jurisdiction_states'],
                    'address' => $c['address'],
                    'official_url' => $c['official_url'],
                    'ecourts_url' => $c['ecourts_url'],
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
            $hcMap[$c['court_name']] = $hc->id;
        }

        // 2. Seed District Court Complexes (Delhi District Courts & Gautam Buddh Nagar)
        $complexes = [
            [
                'state_name' => 'Delhi',
                'high_court' => 'High Court of Delhi',
                'judicial_district' => 'Central & West Delhi',
                'complex_name' => 'Tis Hazari Court Complex',
                'address' => 'Tis Hazari, New Delhi, Delhi 110054',
                'city' => 'New Delhi',
                'pincode' => '110054',
                'official_url' => 'https://delhidistrictcourts.nic.in/',
            ],
            [
                'state_name' => 'Delhi',
                'high_court' => 'High Court of Delhi',
                'judicial_district' => 'New Delhi',
                'complex_name' => 'Patiala House Court Complex',
                'address' => 'India Gate Circle, Tilak Marg, New Delhi, Delhi 110001',
                'city' => 'New Delhi',
                'pincode' => '110001',
                'official_url' => 'https://newdelhi.dcourts.gov.in/',
            ],
            [
                'state_name' => 'Delhi',
                'high_court' => 'High Court of Delhi',
                'judicial_district' => 'East, North-East & Shahdara',
                'complex_name' => 'Karkardooma Court Complex',
                'address' => 'Karkardooma, Anand Vihar, New Delhi, Delhi 110032',
                'city' => 'New Delhi',
                'pincode' => '110032',
                'official_url' => 'https://eastdelhi.dcourts.gov.in/',
            ],
            [
                'state_name' => 'Delhi',
                'high_court' => 'High Court of Delhi',
                'judicial_district' => 'North & North-West Delhi',
                'complex_name' => 'Rohini Court Complex',
                'address' => 'Sector 14, Rohini, New Delhi, Delhi 110085',
                'city' => 'New Delhi',
                'pincode' => '110085',
                'official_url' => 'https://rohini.dcourts.gov.in/',
            ],
            [
                'state_name' => 'Delhi',
                'high_court' => 'High Court of Delhi',
                'judicial_district' => 'South-West Delhi',
                'complex_name' => 'Dwarka Court Complex',
                'address' => 'Sector 10, Dwarka, New Delhi, Delhi 110075',
                'city' => 'New Delhi',
                'pincode' => '110075',
                'official_url' => 'https://dwarka.dcourts.gov.in/',
            ],
            [
                'state_name' => 'Delhi',
                'high_court' => 'High Court of Delhi',
                'judicial_district' => 'South & South-East Delhi',
                'complex_name' => 'Saket Court Complex',
                'address' => 'Sector 6, Pushp Vihar, Saket, New Delhi, Delhi 110017',
                'city' => 'New Delhi',
                'pincode' => '110017',
                'official_url' => 'https://saket.dcourts.gov.in/',
            ],
            [
                'state_name' => 'Delhi',
                'high_court' => 'High Court of Delhi',
                'judicial_district' => 'Rouse Avenue (Special / CBI / Labour Courts)',
                'complex_name' => 'Rouse Avenue Court Complex',
                'address' => 'Deen Dayal Upadhyay Marg, New Delhi, Delhi 110002',
                'city' => 'New Delhi',
                'pincode' => '110002',
                'official_url' => 'https://rouseavenue.dcourts.gov.in/',
            ],
            [
                'state_name' => 'Uttar Pradesh',
                'high_court' => 'High Court of Judicature at Allahabad',
                'judicial_district' => 'Gautam Buddh Nagar',
                'complex_name' => 'District & Sessions Court, Gautam Buddh Nagar',
                'address' => 'Surajpur, Greater Noida, Gautam Buddh Nagar, Uttar Pradesh 201306',
                'city' => 'Greater Noida',
                'pincode' => '201306',
                'official_url' => 'https://gautambudhnagar.dcourts.gov.in/',
            ],
        ];

        foreach ($complexes as $cx) {
            $state = DB::table('states')->where('name', $cx['state_name'])->first();
            if (!$state) continue;

            CourtComplex::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'complex_name' => $cx['complex_name'],
                ],
                [
                    'high_court_id' => $hcMap[$cx['high_court']] ?? null,
                    'judicial_district_name' => $cx['judicial_district'],
                    'complex_type' => 'District & Sessions Court',
                    'address' => $cx['address'],
                    'city' => $cx['city'],
                    'pincode' => $cx['pincode'],
                    'official_url' => $cx['official_url'],
                    'ecourts_url' => 'https://services.ecourts.gov.in/',
                    'source_name' => 'eCourts District Court Portals (dcourts.gov.in)',
                    'source_url' => $cx['official_url'],
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Verified Supreme Court, High Courts, and District Court Complexes seeded successfully.');
    }
}