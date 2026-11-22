<?php

namespace Tests\Feature;

use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvocateProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.advocate.index'))
            ->assertRedirect(route('login'));
    }

    public function test_renders_the_state_and_union_territory_dropdown(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.advocate.index'))
            ->assertOk()
            ->assertSee('<optgroup label="States">', false)
            ->assertSee('<optgroup label="Union Territories">', false)
            ->assertSeeInOrder(['value="Uttar Pradesh"', 'data-state-code="IN-UP"', 'data-gst-code="09"'], false)
            ->assertSeeInOrder(['value="Puducherry"', 'data-state-code="IN-PY"', 'data-gst-code="34"'], false)
            ->assertDontSee('data-state-id=', false);
    }

    public function test_state_options_carry_the_database_id_once_states_are_seeded(): void
    {
        $uttarPradesh = State::create(['name' => 'Uttar Pradesh', 'code' => 'IN-UP']);

        $this->actingAs(User::factory()->create())
            ->get(route('profile.advocate.index'))
            ->assertOk()
            ->assertSeeInOrder(['value="Uttar Pradesh"', 'data-state-id="'.$uttarPradesh->id.'"'], false);
    }

    public function test_saving_a_listed_state_stores_its_state_code_from_the_server_list(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.advocate.update'), $this->validPayload([
                'region' => 'Uttar Pradesh',
                'state_code' => 'TAMPERED',
                'photo' => UploadedFile::fake()->create('advocate.jpg', 20, 'image/jpeg'),
            ]))
            ->assertRedirect(route('profile.advocate.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('advocate_profiles', [
            'user_id' => $user->id,
            'city' => 'Lucknow',
            'region' => 'Uttar Pradesh',
            'state_code' => 'IN-UP',
        ]);
    }

    public function test_rejects_a_state_that_is_not_in_the_official_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.advocate.index'))
            ->post(route('profile.advocate.update'), $this->validPayload([
                'region' => 'Uttar Pardesh',
                'photo' => UploadedFile::fake()->create('advocate.jpg', 20, 'image/jpeg'),
            ]))
            ->assertRedirect(route('profile.advocate.index'))
            ->assertSessionHasErrors(['region' => 'Select a State or Union Territory from the list.']);

        $this->assertDatabaseMissing('advocate_profiles', ['user_id' => $user->id]);
    }

    public function test_student_intern_can_save_without_a_photo(): void
    {
        $user = User::factory()->create(['account_role' => 'student_intern']);

        $this->actingAs($user)
            ->post(route('profile.advocate.update'), $this->validPayload(['region' => 'Delhi']))
            ->assertRedirect(route('profile.advocate.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('advocate_profiles', [
            'user_id' => $user->id,
            'region' => 'Delhi',
            'state_code' => 'IN-DL',
            'photo_path' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }
}
