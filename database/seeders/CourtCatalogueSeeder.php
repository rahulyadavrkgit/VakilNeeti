<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\CourtType;
use App\Models\State;
use Illuminate\Database\Seeder;

class CourtCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * These codes are VakilNeeti internal stable keys.
         * They are not external eCourts identifiers.
         */

        $states = [
            ['Andhra Pradesh', 'IN-AP', false],
            ['Arunachal Pradesh', 'IN-AR', false],
            ['Assam', 'IN-AS', false],
            ['Bihar', 'IN-BR', false],
            ['Chhattisgarh', 'IN-CG', false],
            ['Goa', 'IN-GA', false],
            ['Gujarat', 'IN-GJ', false],
            ['Haryana', 'IN-HR', false],
            ['Himachal Pradesh', 'IN-HP', false],
            ['Jharkhand', 'IN-JH', false],
            ['Karnataka', 'IN-KA', false],
            ['Kerala', 'IN-KL', false],
            ['Madhya Pradesh', 'IN-MP', false],
            ['Maharashtra', 'IN-MH', false],
            ['Manipur', 'IN-MN', false],
            ['Meghalaya', 'IN-ML', false],
            ['Mizoram', 'IN-MZ', false],
            ['Nagaland', 'IN-NL', false],
            ['Odisha', 'IN-OD', false],
            ['Punjab', 'IN-PB', false],
            ['Rajasthan', 'IN-RJ', false],
            ['Sikkim', 'IN-SK', false],
            ['Tamil Nadu', 'IN-TN', false],
            ['Telangana', 'IN-TS', false],
            ['Tripura', 'IN-TR', false],
            ['Uttar Pradesh', 'IN-UP', false],
            ['Uttarakhand', 'IN-UK', false],
            ['West Bengal', 'IN-WB', false],

            ['Andaman and Nicobar Islands', 'IN-AN', true],
            ['Chandigarh', 'IN-CH', true],
            ['Dadra and Nagar Haveli and Daman and Diu', 'IN-DH', true],
            ['Delhi', 'IN-DL', true],
            ['Jammu and Kashmir', 'IN-JK', true],
            ['Ladakh', 'IN-LA', true],
            ['Lakshadweep', 'IN-LD', true],
            ['Puducherry', 'IN-PY', true],
        ];

        foreach ($states as $index => $state) {
            State::updateOrCreate(
                ['code' => $state[1]],
                [
                    'name' => $state[0],
                    'country_code' => 'IN',
                    'is_union_territory' => $state[2],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        /*
         * Court classification options.
         */

        $courtTypes = [
            [
                'name' => 'Supreme Court',
                'slug' => 'supreme-court',
                'requires_state' => false,
            ],
            [
                'name' => 'High Court',
                'slug' => 'high-court',
                'requires_state' => true,
            ],
            [
                'name' => 'District Courts',
                'slug' => 'district-courts',
                'requires_state' => true,
            ],
            [
                'name' => 'Tribunal',
                'slug' => 'tribunal',
                'requires_state' => true,
            ],
            [
                'name' => 'Special Court',
                'slug' => 'special-court',
                'requires_state' => true,
            ],
            [
                'name' => 'Other Judicial Institution',
                'slug' => 'other-judicial-institution',
                'requires_state' => true,
            ],
        ];

        foreach ($courtTypes as $index => $type) {
            CourtType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'requires_state' => $type['requires_state'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        /*
         * Create one canonical Supreme Court selection record.
         * This is a system reference record, not an API import.
         */

        $supremeCourtType = CourtType::where(
            'slug',
            'supreme-court'
        )->firstOrFail();

        $supremeCourt = Court::withTrashed()
            ->where('court_code', 'SC-INDIA')
            ->first();

        if (!$supremeCourt) {
            $supremeCourt = new Court();
        }

        $supremeCourt->fill([
            'court_name' => 'Supreme Court of India',
            'court_code' => 'SC-INDIA',
            'court_type' => 'Supreme Court',
            'court_level' => 'National',
            'country' => 'India',
            'state' => 'Delhi',
            'city' => 'New Delhi',
            'official_website' => 'https://www.sci.gov.in',
            'is_active' => true,
            'state_id' => null,
            'court_type_id' => $supremeCourtType->id,
        ]);

        $supremeCourt->save();

        if ($supremeCourt->trashed()) {
            $supremeCourt->restore();
        }
    }
}
