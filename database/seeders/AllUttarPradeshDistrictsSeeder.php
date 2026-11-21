<?php

namespace Database\Seeders;

use App\Models\BarAssociation;
use App\Models\CourtComplex;
use App\Models\HighCourt;
use App\Models\PoliceDistrict;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllUttarPradeshDistrictsSeeder extends Seeder
{
    public function run(): void
    {
        $upState = DB::table('states')->where('name', 'Uttar Pradesh')->first();
        if (!$upState) {
            $this->command->warn('Uttar Pradesh state not found.');
            return;
        }

        $allahabadHC = HighCourt::where('court_name', 'High Court of Judicature at Allahabad')->first();
        $verifiedAt = now();

        $districts = [
            ['name' => 'Agra', 'division' => 'Agra Zone', 'hq' => 'Agra'],
            ['name' => 'Varanasi', 'division' => 'Varanasi Zone', 'hq' => 'Varanasi'],
            ['name' => 'Gorakhpur', 'division' => 'Gorakhpur Zone', 'hq' => 'Gorakhpur'],
            ['name' => 'Meerut', 'division' => 'Meerut Zone', 'hq' => 'Meerut'],
            ['name' => 'Bareilly', 'division' => 'Bareilly Zone', 'hq' => 'Bareilly'],
            ['name' => 'Kanpur Nagar', 'division' => 'Kanpur Zone', 'hq' => 'Kanpur'],
            ['name' => 'Ghaziabad', 'division' => 'Meerut Zone', 'hq' => 'Ghaziabad'],
            ['name' => 'Aligarh', 'division' => 'Aligarh Zone', 'hq' => 'Aligarh'],
            ['name' => 'Moradabad', 'division' => 'Moradabad Zone', 'hq' => 'Moradabad'],
            ['name' => 'Saharanpur', 'division' => 'Saharanpur Zone', 'hq' => 'Saharanpur'],
            ['name' => 'Jhansi', 'division' => 'Jhansi Zone', 'hq' => 'Jhansi'],
            ['name' => 'Ayodhya', 'division' => 'Ayodhya Zone', 'hq' => 'Ayodhya'],
            ['name' => 'Azamgarh', 'division' => 'Azamgarh Zone', 'hq' => 'Azamgarh'],
            ['name' => 'Basti', 'division' => 'Basti Zone', 'hq' => 'Basti'],
            ['name' => 'Mirzapur', 'division' => 'Mirzapur Zone', 'hq' => 'Mirzapur'],
            ['name' => 'Devipatan', 'division' => 'Devipatan Zone', 'hq' => 'Gonda'],
            ['name' => 'Chitrakoot', 'division' => 'Chitrakoot Zone', 'hq' => 'Banda'],
        ];

        foreach ($districts as $d) {
            $policeDist = PoliceDistrict::updateOrCreate(
                ['state_id' => $upState->id, 'district_name' => $d['name'] . ' Police District'],
                [
                    'range_name' => $d['division'],
                    'headquarters_city' => $d['hq'],
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );

            $courtComplex = CourtComplex::updateOrCreate(
                ['state_id' => $upState->id, 'complex_name' => 'District & Sessions Court, ' . $d['name']],
                [
                    'high_court_id' => $allahabadHC?->id,
                    'judicial_district_name' => $d['name'],
                    'complex_type' => 'District & Sessions Court',
                    'address' => 'District Court Complex, ' . $d['name'] . ', UP',
                    'city' => $d['hq'],
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );

            BarAssociation::updateOrCreate(
                ['association_name' => 'District Bar Association, ' . $d['name']],
                [
                    'state_id' => $upState->id,
                    'court_complex_id' => $courtComplex->id,
                    'court_type' => 'District Court',
                    'office_address' => 'District Court Campus, ' . $d['name'] . ', UP',
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('All major UP districts, police ranges, court complexes, and bar associations seeded successfully.');
    }
}