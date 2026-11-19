<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\CourtType;
use App\Models\State;
use App\Models\InstitutionCategory;
use App\Models\InstitutionJurisdiction;
use Illuminate\Database\Seeder;

class HighCourtSeeder extends Seeder
{
    public function run(): void
    {
        $courtType = CourtType::where('slug', 'high-court')->firstOrFail();

        $category = InstitutionCategory::where(
            'category_key',
            'high_courts'
        )->firstOrFail();

        $sourceUrl = 'https://www.doj.gov.in/static/uploads/2026/04/aa21cd29d4b4a580d660c8c472e24ecd.pdf';

        $highCourts = [
            ['Allahabad High Court', 'Prayagraj', ['Uttar Pradesh']],
            ['Andhra Pradesh High Court', 'Amaravati', ['Andhra Pradesh']],
            ['Bombay High Court', 'Mumbai', ['Maharashtra', 'Goa', 'Dadra and Nagar Haveli and Daman and Diu']],
            ['Calcutta High Court', 'Kolkata', ['West Bengal', 'Andaman and Nicobar Islands']],
            ['Chhattisgarh High Court', 'Bilaspur', ['Chhattisgarh']],
            ['Delhi High Court', 'New Delhi', ['Delhi']],
            ['Gauhati High Court', 'Guwahati', ['Assam', 'Nagaland', 'Mizoram', 'Arunachal Pradesh']],
            ['Gujarat High Court', 'Ahmedabad', ['Gujarat']],
            ['Himachal Pradesh High Court', 'Shimla', ['Himachal Pradesh']],
            ['High Court of Jammu and Kashmir and Ladakh', 'Jammu and Srinagar', ['Jammu and Kashmir', 'Ladakh']],
            ['Jharkhand High Court', 'Ranchi', ['Jharkhand']],
            ['Karnataka High Court', 'Bengaluru', ['Karnataka']],
            ['Kerala High Court', 'Kochi', ['Kerala', 'Lakshadweep']],
            ['Madhya Pradesh High Court', 'Jabalpur', ['Madhya Pradesh']],
            ['Madras High Court', 'Chennai', ['Tamil Nadu', 'Puducherry']],
            ['Manipur High Court', 'Imphal', ['Manipur']],
            ['Meghalaya High Court', 'Shillong', ['Meghalaya']],
            ['Orissa High Court', 'Cuttack', ['Odisha']],
            ['Patna High Court', 'Patna', ['Bihar']],
            ['Punjab and Haryana High Court', 'Chandigarh', ['Punjab', 'Haryana', 'Chandigarh']],
            ['Rajasthan High Court', 'Jodhpur', ['Rajasthan']],
            ['Sikkim High Court', 'Gangtok', ['Sikkim']],
            ['Telangana High Court', 'Hyderabad', ['Telangana']],
            ['Tripura High Court', 'Agartala', ['Tripura']],
            ['Uttarakhand High Court', 'Nainital', ['Uttarakhand']],
        ];

        foreach ($highCourts as [$name, $seat, $jurisdictions]) {

            $stateId = count($jurisdictions) === 1
                ? State::where('name', $jurisdictions[0])->value('id')
                : null;

            $court = Court::withTrashed()
                ->firstOrNew(['court_name' => $name]);

            $court->fill([
                'court_code' => $court->court_code,
                'court_type' => 'High Court',
                'court_level' => 'High Court',
                'country' => 'India',
                'state' => implode(', ', $jurisdictions),
                'city' => $seat,
                'description' => "Principal seat: {$seat}. Territorial jurisdiction: "
                    . implode(', ', $jurisdictions)
                    . ". Source: Department of Justice official High Court directory.",
                'is_active' => true,
                'state_id' => $stateId,
                'court_type_id' => $courtType->id,
            ]);

            if ($court->exists && $court->trashed()) {
                $court->restore();
            }

            $court->save();

            $court->institutionCategories()->syncWithoutDetaching([
                $category->id => ['is_primary' => true],
            ]);

            foreach ($jurisdictions as $stateName) {

                $state = State::where('name', $stateName)->first();

                if (!$state) {
                    $this->command->warn(
                        "State not found: {$stateName} ({$name})"
                    );

                    continue;
                }

                InstitutionJurisdiction::firstOrCreate([
                    'court_id' => $court->id,
                    'state_id' => $state->id,
                    'judicial_district_id' => null,
                    'jurisdiction_type' => 'territorial',
                ], [
                    'description' => "Territorial jurisdiction of {$name}. "
                        . "Source: {$sourceUrl}",
                ]);
            }
        }

        $this->command->info('High Court catalogue seeding completed.');
    }
}
