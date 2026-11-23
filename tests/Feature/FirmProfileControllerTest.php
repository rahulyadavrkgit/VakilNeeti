<?php

namespace Tests\Feature;

use App\Models\FirmProfile;
use App\Models\User;
use App\Services\ProfileCompletion;
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

    public function test_individual_advocates_can_add_office_details_prefilled_from_their_account(): void
    {
        $advocate = User::factory()->create(['account_type' => 'individual', 'name' => 'Rahul Yadav']);

        $this->actingAs($advocate)
            ->get(route('firm-profile.index'))
            ->assertOk()
            ->assertSee('Office &amp; billing details', false)
            ->assertSee('Office or chamber name')
            ->assertSee('value="Adv. Rahul Yadav"', false);

        $this->post(route('firm-profile.update'), [
            'firm_name' => 'Adv. Rahul Yadav',
            'address' => 'Chamber 412, Saket Courts',
            'state' => 'Delhi',
        ])->assertRedirect(route('firm-profile.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('firm_profiles', ['user_id' => $advocate->id, 'address' => 'Chamber 412, Saket Courts', 'is_active' => true]);
        $this->assertNotContains('Add your office address for invoices', array_column(app(ProfileCompletion::class)->forUser($advocate)['missing'], 'label'));
    }

    public function test_bank_and_upi_link_leads_through_office_details_to_invoice_settings(): void
    {
        $this->actingAs(User::factory()->create(['account_type' => 'individual']));

        $this->get(route('profile.invoice-settings.index'))
            ->assertRedirect(route('firm-profile.index', ['next' => 'invoice-settings']));

        $this->get(route('firm-profile.index', ['next' => 'invoice-settings']))
            ->assertOk()
            ->assertSee('Your bank and UPI details come next')
            ->assertSee('<input type="hidden" name="next" value="invoice-settings">', false)
            ->assertSee('Save and add bank / UPI details');

        $this->post(route('firm-profile.update'), ['firm_name' => 'Adv. Meera', 'next' => 'invoice-settings'])
            ->assertRedirect(route('profile.invoice-settings.index'));

        $this->get(route('profile.invoice-settings.index'))->assertOk()->assertSee('name="upi_id"', false);
    }

    private function companyUser(): User
    {
        return User::factory()->create(['account_type' => 'company', 'account_role' => 'law_firm']);
    }
}
