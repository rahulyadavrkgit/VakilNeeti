<?php

namespace Tests\Feature;

use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MergeRenamedUttarPradeshJudicialDistrictsTest extends TestCase
{
    use RefreshDatabase;

    public function test_court_named_district_takes_the_official_name_and_the_duplicate_is_removed(): void
    {
        $uttarPradesh = State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);
        $courtRowId = $this->insertDistrict($uttarPradesh->id, 'Allahabad', null, 'https://prayagraj.dcourts.gov.in/');
        $this->insertDistrict($uttarPradesh->id, 'Prayagraj', '120', 'https://data.gov.in/');

        $this->runMigration();

        $this->assertDatabaseCount('judicial_districts', 1);
        $this->assertDatabaseHas('judicial_districts', [
            'id' => $courtRowId,
            'district_name' => 'Prayagraj',
            'district_code' => '120',
            'source_url' => 'https://prayagraj.dcourts.gov.in/',
        ]);
    }

    public function test_renames_a_court_named_district_that_has_no_duplicate(): void
    {
        $uttarPradesh = State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);
        $this->insertDistrict($uttarPradesh->id, 'Faizabad', null, 'https://ayodhya.dcourts.gov.in/');

        $this->runMigration();

        $this->assertDatabaseHas('judicial_districts', ['district_name' => 'Ayodhya']);
        $this->assertDatabaseMissing('judicial_districts', ['district_name' => 'Faizabad']);
    }

    public function test_updates_the_old_district_name_saved_on_an_advocate_profile(): void
    {
        State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);
        $user = User::factory()->create();
        $user->advocateProfile()->create(['region' => 'Uttar Pradesh', 'district' => 'Jyotiba Phule Nagar']);

        $this->runMigration();

        $this->assertDatabaseHas('advocate_profiles', ['user_id' => $user->id, 'district' => 'Amroha']);
    }

    private function insertDistrict(int $stateId, string $name, ?string $code, string $sourceUrl): int
    {
        return DB::table('judicial_districts')->insertGetId([
            'state_id' => $stateId,
            'district_name' => $name,
            'district_code' => $code,
            'is_active' => true,
            'source_url' => $sourceUrl,
            'source_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_11_22_121827_merge_renamed_uttar_pradesh_judicial_districts.php');
        $migration->up();
    }
}
