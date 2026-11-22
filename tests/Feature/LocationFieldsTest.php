<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Client;
use App\Models\FirmProfile;
use App\Models\JudicialDistrict;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cities_api_lists_the_active_cities_of_a_district(): void
    {
        $state = State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);
        $lucknow = JudicialDistrict::create(['state_id' => $state->id, 'district_name' => 'Lucknow', 'is_active' => true]);
        $agra = JudicialDistrict::create(['state_id' => $state->id, 'district_name' => 'Agra', 'is_active' => true]);
        City::create(['state_id' => $state->id, 'judicial_district_id' => $lucknow->id, 'name' => 'Malihabad', 'pincode' => '226102']);
        City::create(['state_id' => $state->id, 'judicial_district_id' => $lucknow->id, 'name' => 'Closed Town', 'is_active' => false]);
        City::create(['state_id' => $state->id, 'judicial_district_id' => $agra->id, 'name' => 'Fatehabad']);

        $this->getJson(route('api.locations.cities', $lucknow->id))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.name', 'Malihabad')
            ->assertJsonPath('data.0.pincode', '226102');
    }

    public function test_cities_api_is_available_without_logging_in_for_registration(): void
    {
        $state = State::create(['name' => 'Kerala', 'code' => 'IN-KL']);
        $district = JudicialDistrict::create(['state_id' => $state->id, 'district_name' => 'Ernakulam', 'is_active' => true]);

        $this->getJson(route('api.locations.cities', $district->id))->assertOk();
    }

    public function test_client_form_shows_country_state_district_and_city_dropdowns(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('clients.create'))
            ->assertOk()
            ->assertSee('data-location-country-select', false)
            ->assertSee('data-location-state', false)
            ->assertSee('data-location-district-select', false)
            ->assertSee('data-location-city-select', false)
            ->assertSee('value="Uttar Pradesh"', false);
    }

    public function test_client_in_india_must_use_a_state_from_the_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('clients.store'), ['client_type' => 'individual', 'first_name' => 'Asha', 'country' => 'India', 'state' => 'UP'])
            ->assertSessionHasErrors('state');

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_client_outside_india_can_type_any_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('clients.store'), ['client_type' => 'individual', 'first_name' => 'Asha', 'country' => 'Nepal', 'state' => 'Bagmati', 'city' => 'Kathmandu'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clients', ['first_name' => 'Asha', 'country' => 'Nepal', 'state' => 'Bagmati', 'city' => 'Kathmandu']);
    }

    public function test_client_edit_form_has_a_single_set_of_location_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['first_name' => 'Asha', 'state' => 'Kerala', 'city' => 'Kochi', 'country' => 'India']);

        $html = $this->get(route('clients.edit', $client))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'name="city"'));
        $this->assertSame(1, substr_count($html, 'name="district"'));
        $this->assertSame(1, substr_count($html, 'name="country"'));
    }

    public function test_professional_tax_requires_a_state_from_the_list(): void
    {
        $this->actingAs(User::factory()->create(['account_type' => 'company']));
        FirmProfile::create(['firm_name' => 'Sharma Legal Associates', 'is_active' => true]);

        $this->post(route('professional-tax.store'), ['state' => 'Not A State'])
            ->assertSessionHasErrors('state');
    }
}
