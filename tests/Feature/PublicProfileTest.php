<?php

namespace Tests\Feature;

use App\Models\AdvocateProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cv_is_built_from_the_advocate_profile_without_retyping(): void
    {
        $owner = $this->advocate();

        $this->actingAs($owner)->get(route('profile.cv.edit'))->assertOk()
            ->assertSee('value="asha-sharma"', false)
            ->assertDontSee('name="values[', false);

        $this->get(route('profile.cv.preview'))->assertOk()
            ->assertSee('Asha Sharma')->assertSee('Family')->assertSee('Civil')->assertSee('Property disputes')
            ->assertSee('District Court Lucknow')->assertSee('12 years in practice')->assertSee('UP/1234/2014')
            ->assertSee('Hindi, English')->assertSee('Lucknow, Uttar Pradesh')
            ->assertSee('asha@example.test')->assertDontSee('+919876543210');

        $download = $this->get(route('profile.cv.download'))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')->assertDownload('asha-sharma-cv.pdf');
        $this->assertStringStartsWith('%PDF-', $download->getContent());
    }

    public function test_profile_changes_show_on_the_page_without_saving_the_cv_again(): void
    {
        $owner = $this->advocate();
        $this->actingAs($owner)->put(route('profile.cv.update'), $this->settings(['publish' => true]))->assertSessionHasNoErrors();

        $owner->advocateProfile->update(['court' => 'Allahabad High Court, Lucknow Bench']);
        $this->get('/cv/asha-sharma')->assertOk()->assertSee('Allahabad High Court, Lucknow Bench');
    }

    public function test_the_owner_chooses_the_introduction_and_contact_details(): void
    {
        $owner = $this->advocate();
        $this->actingAs($owner)->put(route('profile.cv.update'), $this->settings([
            'about' => 'Twelve years in family and civil courts.',
            'show_email' => false,
            'show_phone' => true,
        ]))->assertRedirect(route('profile.cv.edit'));

        $this->get(route('profile.cv.preview'))->assertOk()
            ->assertSee('Twelve years in family and civil courts.')
            ->assertSee('href="tel:+919876543210"', false)
            ->assertDontSee('asha@example.test');
    }

    public function test_private_pages_stay_private_until_published(): void
    {
        $owner = $this->advocate();
        $this->actingAs($owner)->put(route('profile.cv.update'), $this->settings())->assertSessionHasNoErrors();

        $this->assertNull($owner->fresh()->public_cv_published_at);
        $this->get('/cv/asha-sharma')->assertNotFound();
        $this->get('/cv/asha-sharma/download')->assertNotFound();

        $this->post(route('user.logout'));
        $this->get(route('profile.cv.preview'))->assertRedirect();
        $this->get(route('profile.cv.download'))->assertRedirect();
    }

    public function test_published_page_is_shareable_by_username_and_hides_private_details(): void
    {
        $owner = $this->advocate();
        $this->actingAs($owner)->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => '  Asha-Sharma ']))
            ->assertSessionHasNoErrors();
        $this->assertSame('asha-sharma', $owner->fresh()->public_cv_username);
        $this->get(route('profile.cv.edit'))->assertOk()->assertSee(route('cv.show', 'asha-sharma'));
        $this->post(route('user.logout'));

        $this->get('/cv/asha-sharma')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('Asha Sharma')->assertSee('WhatsApp')->assertSee('Copy link')
            ->assertDontSee('+919876543210')->assertDontSee('Preview ·');
        $this->get('/cv/Asha-Sharma')->assertOk();
        $this->get('/cv/asha-sharma/download')->assertOk()->assertDownload('asha-sharma-cv.pdf');
    }

    public function test_back_shows_for_the_owner_and_site_visitors_but_not_for_shared_links(): void
    {
        $owner = $this->advocate();
        $this->actingAs($owner)->put(route('profile.cv.update'), $this->settings(['publish' => true]));

        $this->get('/cv/asha-sharma')->assertSee('data-vn-back', false)->assertSee('href="'.route('profile.cv.edit').'"', false);
        $this->get(route('profile.cv.preview', ['embed' => 1]))->assertDontSee('data-vn-back', false);

        $this->post(route('user.logout'));
        $this->get('/cv/asha-sharma')->assertDontSee('data-vn-back', false);
        $this->get('/cv/asha-sharma', ['referer' => url('/courts')])->assertSee('data-vn-back', false);
        $this->get('/cv/asha-sharma', ['referer' => 'https://wa.me/somewhere'])->assertDontSee('data-vn-back', false);
    }

    public function test_unpublishing_or_renaming_switches_off_the_old_link(): void
    {
        $owner = $this->advocate();
        $this->actingAs($owner)->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => 'asha']));

        $this->put(route('profile.cv.update'), $this->settings(['username' => 'asha']))->assertSessionHasNoErrors();
        $this->get('/cv/asha')->assertNotFound();
        $this->assertSame('asha', $owner->fresh()->public_cv_username);

        $this->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => 'asha-law']))->assertSessionHasNoErrors();
        $this->get('/cv/asha')->assertNotFound();
        $this->get('/cv/asha-law')->assertOk();
    }

    public function test_usernames_must_be_unique_valid_and_not_reserved(): void
    {
        $taken = $this->advocate();
        $this->actingAs($taken)->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => 'asha']));

        $other = User::factory()->create();
        $this->actingAs($other);
        $this->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => 'asha']))
            ->assertSessionHasErrors(['username' => 'That username is already taken. Please choose another.']);
        $this->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => '']))
            ->assertSessionHasErrors(['username' => 'Choose a username for your page link before publishing.']);
        $this->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => 'vakilniti']))
            ->assertSessionHasErrors('username');

        foreach (['ab', 'asha law', '-asha', 'asha-', 'a--b', 'asha_law', str_repeat('a', 31)] as $invalid) {
            $this->put(route('profile.cv.update'), $this->settings(['publish' => true, 'username' => $invalid]))
                ->assertSessionHasErrors('username');
        }

        $this->put(route('profile.cv.update'), $this->settings(['user_id' => $taken->id]))->assertSessionHasNoErrors();
        $this->assertSame('asha', $taken->fresh()->public_cv_username);
    }

    public function test_a_sparse_profile_still_renders_and_company_accounts_have_no_cv(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Ravi Kumar']))
            ->get(route('profile.cv.preview'))->assertOk()->assertSee('Ravi Kumar')->assertSee('RK');

        $this->actingAs(User::factory()->create(['account_type' => 'company']))
            ->get(route('profile.cv.edit'))->assertNotFound();
    }

    private function advocate(): User
    {
        $user = User::factory()->create(['name' => 'Asha Sharma', 'email' => 'asha@example.test', 'mobile' => '+919876543210']);
        AdvocateProfile::forceCreate([
            'user_id' => $user->id,
            'years_experience' => 12,
            'graduation_year' => 2013,
            'languages_spoken' => ['Hindi', 'English'],
            'specializations' => ['Family', 'Civil'],
            'practice_areas' => 'Property disputes, civil',
            'city' => 'Lucknow',
            'region' => 'Uttar Pradesh',
            'court' => 'District Court Lucknow',
            'bar_council_registration_number' => 'UP/1234/2014',
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settings(array $overrides = []): array
    {
        return $overrides + ['username' => 'asha-sharma', 'about' => '', 'show_email' => true, 'show_phone' => false, 'publish' => false];
    }
}
