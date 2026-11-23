<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_starts_at_the_first_unfinished_step_with_skip_and_finish_later(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('profile.setup.start'))
            ->assertRedirect(route('profile.advocate.index', ['setup' => 1]));

        $this->get(route('profile.advocate.index', ['setup' => 1]))
            ->assertOk()
            ->assertSee('Profile setup · step 1 of 4')
            ->assertSee('href="'.route('profile.setup.skip', 'advocate').'"', false)
            ->assertSee('Finish later')
            ->assertSee('<input type="hidden" name="setup" value="1">', false);
    }

    public function test_saving_the_advocate_profile_moves_on_to_the_next_step(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post(route('profile.advocate.update'), [
            'setup' => 1,
            'full_name' => 'Asha Advocate',
            'email' => 'asha@example.test',
            'gender' => 'Female',
            'years_experience' => 5,
            'graduation_year' => 2018,
            'languages_spoken' => 'Hindi, English',
            'specializations' => ['Civil'],
            'city' => 'Lucknow',
            'district' => 'Lucknow',
            'region' => 'Uttar Pradesh',
            'pincode' => '226001',
            'court' => 'District Court Lucknow',
            'bar_state' => 'Uttar Pradesh',
            'bar_state_code' => 'UP',
            'bar_number' => '1234',
            'bar_registration_year' => 2017,
            'photo' => UploadedFile::fake()->create('advocate.jpg', 20, 'image/jpeg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.setup.next', ['after' => 'advocate']));

        $this->get(route('profile.setup.next', ['after' => 'advocate']))
            ->assertRedirect(route('profile.practice-states.index', ['setup' => 1]));

        $this->get(route('profile.practice-states.index', ['setup' => 1]))
            ->assertOk()
            ->assertSee('step 2 of 4')
            ->assertSee('href="'.route('profile.setup.next', ['after' => 'practice-states']).'"', false);
    }

    public function test_skipping_goes_on_and_skipping_the_office_also_skips_bank_details(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('profile.setup.skip', 'advocate'))->assertRedirect(route('profile.practice-states.index', ['setup' => 1]));
        $this->get(route('profile.setup.skip', 'practice-states'))->assertRedirect(route('firm-profile.index', ['setup' => 1]));

        $this->get(route('profile.setup.skip', 'office'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('auth_login_success', fn (string $message) => str_contains($message, 'Finish the skipped steps any time'));
    }

    public function test_saving_the_office_in_setup_leads_to_bank_details_and_a_complete_profile_ends_on_home(): void
    {
        $user = User::factory()->create(['account_type' => 'company', 'account_role' => 'law_firm']);
        $this->actingAs($user);
        DB::table('firm_practice_states')->insert(['user_id' => $user->id, 'state_name' => 'Delhi', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->post(route('firm-profile.update'), ['setup' => 1, 'firm_name' => 'Sharma Legal', 'address' => 'Saket'])
            ->assertRedirect(route('profile.setup.next', ['after' => 'office']));
        $this->get(route('profile.setup.next', ['after' => 'office']))
            ->assertRedirect(route('profile.invoice-settings.index', ['setup' => 1]));

        $firmId = DB::table('firm_profiles')->where('user_id', $user->id)->value('id');
        DB::table('invoice_settings')->insert(['user_id' => $user->id, 'firm_profile_id' => $firmId, 'upi_id' => 'sharma@upi', 'created_at' => now(), 'updated_at' => now()]);

        $this->get(route('profile.setup.next', ['after' => 'billing']))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('auth_login_success', 'Your profile is 100% complete.');
    }
}
