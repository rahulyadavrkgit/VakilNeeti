<?php

namespace Tests\Feature;

use App\Models\FirmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_state_stores_its_gst_state_code(): void
    {
        $this->actingAs($this->companyUser())
            ->post(route('firm-profile.update'), [
                'firm_name' => 'Sharma Legal Associates',
                'state' => 'Uttar Pradesh',
                'state_code' => '99',
                'district' => 'Lucknow',
                'city' => 'Lucknow',
            ])
            ->assertRedirect(route('firm-profile.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('firm_profiles', [
            'firm_name' => 'Sharma Legal Associates',
            'state' => 'Uttar Pradesh',
            'state_code' => '09',
        ]);
    }

    public function test_firm_registered_with_an_old_state_code_can_be_saved_again(): void
    {
        $this->actingAs($this->companyUser());
        $firm = FirmProfile::create([
            'firm_name' => 'Sharma Legal Associates',
            'state' => 'Uttar Pradesh',
            'state_code' => 'IN-UP',
        ]);

        $this
            ->post(route('firm-profile.update'), [
                'firm_name' => 'Sharma Legal Associates',
                'state' => 'Uttar Pradesh',
            ])
            ->assertRedirect(route('firm-profile.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('09', $firm->fresh()->state_code);
    }

    public function test_rejects_a_state_that_is_not_in_the_official_list(): void
    {
        $this->actingAs($this->companyUser())
            ->from(route('firm-profile.index'))
            ->post(route('firm-profile.update'), [
                'firm_name' => 'Sharma Legal Associates',
                'state' => 'UP',
            ])
            ->assertRedirect(route('firm-profile.index'))
            ->assertSessionHasErrors(['state' => 'Select a State or Union Territory from the list.']);

        $this->assertDatabaseCount('firm_profiles', 0);
    }

    private function companyUser(): User
    {
        return User::factory()->create(['account_type' => 'company', 'account_role' => 'law_firm']);
    }
}
