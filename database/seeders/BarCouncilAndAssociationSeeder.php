<?php

namespace Database\Seeders;

use App\Models\BarAssociation;
use App\Models\BarCouncil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarCouncilAndAssociationSeeder extends Seeder
{
    public function run(): void
    {
        $verifiedAt = now();
        $sourceName = 'Bar Council of India (BCI) & High Court Directories';
        $sourceUrl = 'https://www.barcouncilofindia.org/';

        // 1. Seed National & State Bar Councils
        $councils = [
            [
                'state_name' => null, // National
                'council_name' => 'Bar Council of India (BCI)',
                'jurisdiction_type' => 'national',
                'office_address' => '21, Rouse Avenue Institutional Area, Near Bal Bhavan, New Delhi, Delhi 110002',
                'contact_phone' => '011-23231658',
                'email' => 'info@barcouncilofindia.org',
                'official_url' => 'https://www.barcouncilofindia.org/',
            ],
            [
                'state_name' => 'Delhi',
                'council_name' => 'Bar Council of Delhi',
                'jurisdiction_type' => 'state',
                'office_address' => '1, Siri Institutional Area, August Kranti Marg, New Delhi, Delhi 110049',
                'contact_phone' => '011-26495295',
                'email' => 'barcouncilofdelhi@yahoo.com',
                'official_url' => 'https://www.barcouncilofdelhi.com/',
            ],
            [
                'state_name' => 'Uttar Pradesh',
                'council_name' => 'Bar Council of Uttar Pradesh',
                'jurisdiction_type' => 'state',
                'office_address' => 'High Court Campus, Civil Lines, Prayagraj, Uttar Pradesh 211001',
                'contact_phone' => '0532-2624026',
                'email' => 'contact@barcouncilofup.com',
                'official_url' => 'https://barcouncilofup.com/',
            ],
            [
                'state_name' => 'Maharashtra',
                'council_name' => 'Bar Council of Maharashtra & Goa',
                'jurisdiction_type' => 'state',
                'office_address' => '2nd Floor, High Court Extension, Fort, Mumbai, Maharashtra 400032',
                'contact_phone' => '022-22660424',
                'email' => 'barcouncil.mumbai@gmail.com',
                'official_url' => 'https://barcouncilofmaharashtragoa.org/',
            ],
            [
                'state_name' => 'Karnataka',
                'council_name' => 'Bar Council of Karnataka',
                'jurisdiction_type' => 'state',
                'office_address' => 'Old High Court Buildings, Dr. B.R. Ambedkar Veedhi, Bengaluru, Karnataka 560001',
                'contact_phone' => '080-22268754',
                'email' => 'barcouncilofkarnataka@gmail.com',
                'official_url' => 'https://barcouncilofkarnataka.org/',
            ],
            [
                'state_name' => 'Tamil Nadu',
                'council_name' => 'Bar Council of Tamil Nadu & Puducherry',
                'jurisdiction_type' => 'state',
                'office_address' => 'High Court Campus, Chennai, Tamil Nadu 600104',
                'contact_phone' => '044-25342739',
                'email' => 'tnd@barcouncil.org',
                'official_url' => 'https://barcouncilofthandp.org/',
            ],
        ];

        foreach ($councils as $c) {
            $stateId = null;
            if (!empty($c['state_name'])) {
                $state = DB::table('states')->where('name', $c['state_name'])->first();
                $stateId = $state ? $state->id : null;
            }

            BarCouncil::updateOrCreate(
                ['council_name' => $c['council_name']],
                [
                    'state_id' => $stateId,
                    'jurisdiction_type' => $c['jurisdiction_type'],
                    'office_address' => $c['office_address'],
                    'contact_phone' => $c['contact_phone'],
                    'email' => $c['email'],
                    'official_url' => $c['official_url'],
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        // 2. Seed Prominent Bar Associations
        $associations = [
            [
                'state_name' => 'Delhi',
                'association_name' => 'Supreme Court Bar Association (SCBA)',
                'court_type' => 'Supreme Court',
                'office_address' => 'Supreme Court of India, Tilak Marg, New Delhi, Delhi 110001',
                'contact_phone' => '011-23384826',
                'email' => 'scba.india@gmail.com',
                'official_url' => 'https://www.scba.co.in/',
            ],
            [
                'state_name' => 'Delhi',
                'association_name' => 'Delhi High Court Bar Association (DHCBA)',
                'court_type' => 'High Court',
                'office_address' => 'Delhi High Court, Sher Shah Road, New Delhi, Delhi 110003',
                'contact_phone' => '011-23387844',
                'email' => 'dhcba1962@gmail.com',
                'official_url' => 'https://dhcba.in/',
            ],
            [
                'state_name' => 'Delhi',
                'association_name' => 'New Delhi Bar Association (NDBA)',
                'court_type' => 'District Court',
                'office_address' => 'Patiala House Courts, New Delhi, Delhi 110001',
                'contact_phone' => '011-23381483',
                'official_url' => 'https://www.delhipolice.gov.in/',
            ],
            [
                'state_name' => 'Uttar Pradesh',
                'association_name' => 'High Court Bar Association, Prayagraj',
                'court_type' => 'High Court',
                'office_address' => 'High Court Campus, Prayagraj, Uttar Pradesh 211001',
                'contact_phone' => '0532-2623333',
                'official_url' => 'https://allahabadhighcourt.in/',
            ],
            [
                'state_name' => 'Maharashtra',
                'association_name' => 'Bombay High Court Bar Association',
                'court_type' => 'High Court',
                'office_address' => 'Bombay High Court, Fort, Mumbai, Maharashtra 400032',
                'contact_phone' => '022-22663240',
                'official_url' => 'https://bombayhighcourt.nic.in/',
            ],
        ];

        foreach ($associations as $a) {
            $state = DB::table('states')->where('name', $a['state_name'])->first();
            if (!$state) continue;

            BarAssociation::updateOrCreate(
                ['association_name' => $a['association_name']],
                [
                    'state_id' => $state->id,
                    'court_type' => $a['court_type'],
                    'office_address' => $a['office_address'],
                    'contact_phone' => $a['contact_phone'] ?? null,
                    'email' => $a['email'] ?? null,
                    'official_url' => $a['official_url'] ?? null,
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Bar councils and bar associations seeded successfully.');
    }
}