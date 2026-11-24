<?php

namespace Tests\Feature;

use App\Models\AdvocateProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BarRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_saves_the_three_parts_and_the_full_enrolment_number(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.advocate.update'), $this->profilePayload([
                'bar_state' => 'Maharashtra',
                'bar_state_code' => ' mah. ',
                'bar_number' => '01 234',
                'bar_registration_year' => 2015,
            ]))
            ->assertSessionHasNoErrors();

        $profile = AdvocateProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Maharashtra', $profile->bar_state);
        $this->assertSame('MAH', $profile->bar_state_code);
        $this->assertSame('01234', $profile->bar_number);
        $this->assertSame(2015, $profile->bar_registration_year);
        $this->assertSame('MAH/01234/2015', $profile->bar_council_registration_number);
    }

    public function test_rejects_bad_parts_with_clear_messages(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('profile.advocate.update'), $this->profilePayload([
                'bar_state' => 'Atlantis',
                'bar_state_code' => 'U9',
                'bar_number' => '12#4',
                'bar_registration_year' => now()->year + 1,
            ]))
            ->assertSessionHasErrors([
                'bar_state' => 'Choose a State or Union Territory from the list.',
                'bar_state_code' => 'Use letters only for the State code, e.g. UP.',
                'bar_number' => 'Use letters and digits only for the enrolment number, e.g. 1234.',
                'bar_registration_year',
            ]);
    }

    public function test_advocates_must_give_the_enrolment_but_students_may_leave_it_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('profile.advocate.update'), $this->profilePayload(['bar_state' => null, 'bar_state_code' => null, 'bar_number' => null, 'bar_registration_year' => null]))
            ->assertSessionHasErrors(['bar_number' => 'Enter your enrolment number.']);

        $student = User::factory()->create(['account_role' => 'student_intern']);
        $this->actingAs($student)
            ->post(route('profile.advocate.update'), $this->profilePayload(['bar_state' => null, 'bar_state_code' => null, 'bar_number' => null, 'bar_registration_year' => null]))
            ->assertSessionHasNoErrors();

        $this->assertNull(AdvocateProfile::where('user_id', $student->id)->value('bar_council_registration_number'));
    }

    public function test_a_student_who_gives_a_number_must_give_the_other_parts(): void
    {
        $this->actingAs(User::factory()->create(['account_role' => 'student_intern']))
            ->post(route('profile.advocate.update'), $this->profilePayload(['bar_state' => null, 'bar_state_code' => null, 'bar_number' => '1234', 'bar_registration_year' => null]))
            ->assertSessionHasErrors(['bar_state', 'bar_state_code', 'bar_registration_year' => 'Choose your enrolment year.']);
    }

    public function test_profile_page_shows_the_fields_with_suggested_state_codes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.advocate.index'))
            ->assertOk()
            ->assertSee('id="bar-registration"', false)
            ->assertSee('name="bar_state_code"', false)
            ->assertSee('&quot;Uttar Pradesh&quot;:&quot;UP&quot;', false)
            ->assertSee('<option value="1950"', false);
    }

    public function test_signup_saves_the_parts_for_an_advocate(): void
    {
        $this->withSession(['verified_registration' => ['email' => 'new@example.test', 'otp_bypassed' => true]])
            ->post(route('register.profile.submit'), [
                'account_role' => 'independent_practitioner',
                'onboarding_features' => ['personalized_hearings'],
                'full_name' => 'Asha Advocate',
                'email' => 'asha@example.test',
                'bar_state' => 'Uttar Pradesh',
                'bar_state_code' => 'up',
                'bar_number' => '1234',
                'bar_registration_year' => 2017,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cases.find'));

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertDatabaseHas('advocate_profiles', [
            'user_id' => $user->id,
            'bar_state' => 'Uttar Pradesh',
            'bar_state_code' => 'UP',
            'bar_number' => '1234',
            'bar_council_registration_number' => 'UP/1234/2017',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Asha Advocate',
            'email' => 'asha@example.test',
            'gender' => 'Female',
            'photo' => UploadedFile::fake()->create('advocate.jpg', 20, 'image/jpeg'),
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
