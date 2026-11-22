<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\JudicialDistrict;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLgdCitiesTest extends TestCase
{
    use RefreshDatabase;

    private JudicialDistrict $lucknow;

    private JudicialDistrict $bengaluruUrban;

    public function test_imports_cities_with_clean_names_and_their_districts(): void
    {
        $this->seedLocations();

        $this->artisan('locations:import-cities', ['--from-file' => true, '--file' => base_path('tests/Fixtures/lgd_cities_sample.json')])
            ->expectsOutputToContain('Cities saved: 5 (4 linked to a district).')
            ->expectsOutputToContain('Skipped unknown states: Atlantis')
            ->assertSuccessful();

        $this->assertCity('Lucknow', $this->lucknow->id, '226001');
        $this->assertCity('Malihabad', $this->lucknow->id);
        $this->assertCity('Mohanlalganj', $this->lucknow->id);
        $this->assertCity('Bengaluru', $this->bengaluruUrban->id);
        $this->assertCity('Faraway Town', null);
    }

    public function test_reimporting_updates_cities_instead_of_duplicating_them(): void
    {
        $this->seedLocations();
        $arguments = ['--from-file' => true, '--file' => base_path('tests/Fixtures/lgd_cities_sample.json')];

        $this->artisan('locations:import-cities', $arguments)->assertSuccessful();
        $this->artisan('locations:import-cities', $arguments)->assertSuccessful();

        $this->assertDatabaseCount('cities', 5);
    }

    public function test_fails_when_the_snapshot_file_is_missing(): void
    {
        $this->artisan('locations:import-cities', ['--from-file' => true, '--file' => base_path('tests/Fixtures/missing.json')])
            ->expectsOutputToContain('Snapshot not found')
            ->assertFailed();
    }

    private function assertCity(string $name, ?int $districtId, ?string $pincode = null): void
    {
        $city = City::where('name', $name)->first();

        $this->assertNotNull($city, "City {$name} was not imported.");
        $this->assertSame($districtId, $city->judicial_district_id, "Wrong district for {$name}.");

        if ($pincode !== null) {
            $this->assertSame($pincode, $city->pincode);
        }
    }

    private function seedLocations(): void
    {
        $uttarPradesh = State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);
        $karnataka = State::create(['name' => 'Karnataka', 'code' => 'IN-KA']);
        $this->lucknow = JudicialDistrict::create(['state_id' => $uttarPradesh->id, 'district_name' => 'Lucknow', 'district_code' => '162', 'is_active' => true]);
        $this->bengaluruUrban = JudicialDistrict::create(['state_id' => $karnataka->id, 'district_name' => 'Bengaluru Urban', 'district_code' => '525', 'is_active' => true]);
    }
}
