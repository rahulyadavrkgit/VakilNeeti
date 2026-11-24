<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_setup_lists_every_state_without_a_seeded_states_table(): void
    {
        $this->withSession(['verified_registration' => ['email' => 'new@example.test']])
            ->get(route('register.profile'))
            ->assertOk()
            ->assertSee('<optgroup label="States">', false)
            ->assertSeeInOrder(['value="Ladakh"', 'data-gst-code="38"'], false)
            ->assertDontSee('Loading states...');
    }

    public function test_company_registration_stores_the_gst_state_code_for_the_firm(): void
    {
        $this->withSession(['verified_registration' => ['email' => 'new@example.test']])
            ->post(route('register.profile.submit'), $this->companyPayload(['region' => 'Uttar Pradesh']))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'new@example.test')->firstOrFail();

        $this->assertDatabaseHas('firm_profiles', [
            'user_id' => $user->id,
            'state' => 'Uttar Pradesh',
            'state_code' => '09',
        ]);
    }

    public function test_company_registration_rejects_a_state_that_is_not_in_the_official_list(): void
    {
        $this->withSession(['verified_registration' => ['email' => 'new@example.test']])
            ->from(route('register.profile'))
            ->post(route('register.profile.submit'), $this->companyPayload(['region' => 'Uttar Pardesh']))
            ->assertRedirect(route('register.profile'))
            ->assertSessionHasErrors(['region' => 'Select a State or Union Territory from the list.']);

        $this->assertDatabaseMissing('users', ['mobile' => '+919876543210']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function companyPayload(array $overrides = []): array
    {
        return array_merge([
            'account_role' => 'law_firm',
            'onboarding_features' => ['invoices_expenses_documents'],
            'full_name' => 'Asha Sharma',
            'email' => 'asha@firm.example',
            'firm_name' => 'Sharma Legal Associates',
            'city' => 'Lucknow',
            'district' => 'Lucknow',
            'region' => 'Uttar Pradesh',
            'pincode' => '226001',
        ], $overrides);
    }
}
