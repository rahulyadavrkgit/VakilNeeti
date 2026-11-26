<?php

namespace Tests\Feature;

use App\Models\AdvocateProfile;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseFinderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_the_bar_code_and_official_links_without_fetching_anything(): void
    {
        $user = User::factory()->create();
        AdvocateProfile::forceCreate(['user_id' => $user->id, 'bar_state_code' => 'UP', 'bar_number' => '1234', 'bar_registration_year' => 2017, 'court' => 'District Court Lucknow']);

        $this->actingAs($user)
            ->get(route('cases.find'))
            ->assertOk()
            ->assertSee('data-copy="UP"', false)
            ->assertSee('data-copy="1234"', false)
            ->assertSee('https://services.ecourts.gov.in/ecourtindia_v6/')
            ->assertSee('data-direct-share', false)
            ->assertSee('value="District Court Lucknow"', false);
    }

    public function test_page_asks_for_the_enrolment_when_it_is_missing(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('cases.find'))
            ->assertOk()
            ->assertSee('Add it in your profile');
    }

    public function test_preview_lists_found_cases_and_marks_ones_already_added(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Old', 'case_number' => 'O.S./123/2019', 'court_name' => 'Civil Court Lucknow', 'case_status' => 'pending']);

        $response = $this->post(route('cases.find.preview'), [
            'level' => 'district',
            'court_name' => 'Civil Court Lucknow',
            'results' => "1\tO.S./123/2019\tRam vs Shyam\tView\n2\tCS/7/2021\tAsha vs Ravi\tView",
        ])->assertOk()->assertSee('CS/7/2021')->assertSee('Asha vs Ravi');

        $rows = $response->viewData('rows');
        $this->assertTrue($rows[0]['already_added']);
        $this->assertFalse($rows[1]['already_added']);
    }

    public function test_preview_explains_when_nothing_was_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('cases.find'))
            ->post(route('cases.find.preview'), ['level' => 'district', 'results' => 'Invalid Captcha'])
            ->assertRedirect(route('cases.find'))
            ->assertSessionHasErrors('results');
    }

    public function test_import_adds_only_ticked_new_cases_for_the_signed_in_user(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other);
        LegalCase::create(['case_title' => 'Theirs', 'case_number' => 'CS/7/2021', 'court_name' => 'Civil Court Lucknow', 'case_status' => 'pending']);

        $user = User::factory()->create();
        $this->actingAs($user);
        LegalCase::create(['case_title' => 'Mine', 'case_number' => 'O.S./1/2019', 'court_name' => 'Civil Court Lucknow', 'case_status' => 'pending']);

        $this->post(route('cases.find.import'), ['cases' => [
            ['selected' => '1', 'case_title' => 'Asha vs Ravi', 'case_number' => 'CS/7/2021', 'case_type' => 'CS', 'cnr_number' => 'uplk010012342021', 'court_name' => 'Civil Court Lucknow'],
            ['selected' => '0', 'case_title' => 'Skip me', 'case_number' => 'CS/8/2021', 'court_name' => 'Civil Court Lucknow'],
            ['selected' => '1', 'case_title' => 'Mine again', 'case_number' => 'O.S./1/2019', 'court_name' => 'Civil Court Lucknow'],
        ]])->assertRedirect(route('cases.index'))
            ->assertSessionHas('success', '1 case added. 1 was already in your cases. Open a case and use Import court record to add its hearings and full details.');

        $added = LegalCase::where('case_title', 'Asha vs Ravi')->firstOrFail();
        $this->assertSame($user->id, $added->user_id);
        $this->assertSame('UPLK010012342021', $added->cnr_number);
        $this->assertSame('ecourts_guided_import', $added->source_type);
        $this->assertDatabaseMissing('legal_cases', ['case_title' => 'Skip me']);
        $this->assertSame(2, LegalCase::count());
    }

    public function test_import_needs_a_court_for_every_ticked_case(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('cases.find.import'), ['cases' => [
                ['selected' => '1', 'case_title' => 'Asha vs Ravi', 'case_number' => 'CS/7/2021', 'court_name' => ''],
            ]])
            ->assertRedirect(route('cases.find'))
            ->assertSessionHasErrors('cases');

        $this->assertSame(0, LegalCase::count());
    }

    public function test_guests_are_sent_to_log_in(): void
    {
        $this->get(route('cases.find'))->assertRedirect(route('login'));
    }
}
