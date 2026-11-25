<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_signup_ends_with_a_set_your_password_step_and_needs_one(): void
    {
        $this->withSession(['verified_registration' => ['email' => 'new@example.test']])
            ->get(route('register.profile'))->assertOk()
            ->assertSeeInOrder(['Welcome to VakilNiti!', 'I describe myself as', 'What brings you to VakilNiti?', 'Basic information', 'Set your password', 'Complete signup'])
            ->assertSee('name="password" required minlength="8" maxlength="72" autocomplete="new-password"', false)
            ->assertSee('data-onboarding-step="4"', false);

        $this->withSession(['verified_registration' => ['email' => 'new@example.test']])
            ->post(route('register.profile.submit'), $this->companyPayload(['region' => 'Uttar Pradesh', 'password' => null, 'password_confirmation' => null]))
            ->assertSessionHasErrors(['password' => 'Choose a password to sign in with next time.']);
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);

        // Only the password was wrong: back on the form, the pop-up reopens on the password step.
        $this->withSession(['verified_registration' => ['email' => 'new@example.test']])
            ->from(route('register.profile'))->followingRedirects()
            ->post(route('register.profile.submit'), $this->companyPayload(['region' => 'Uttar Pradesh', 'password' => 'diary2026', 'password_confirmation' => 'other2026']))
            ->assertSee('The two passwords do not match.')->assertSee('data-initial-step="4"', false);
    }

    public function test_a_password_chosen_at_signup_works_for_signing_in(): void
    {
        $this->withSession(['verified_registration' => ['email' => 'new@example.test']])
            ->post(route('register.profile.submit'), $this->companyPayload(['region' => 'Uttar Pradesh', 'password' => 'diary2026', 'password_confirmation' => 'diary2026']))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertNotNull($user->password_set_at);
        $this->assertTrue(Hash::check('diary2026', $user->password));
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
            'password' => 'test-password-123',
            'password_confirmation' => 'test-password-123',
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
