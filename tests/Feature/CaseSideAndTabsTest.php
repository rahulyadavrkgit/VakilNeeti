<?php

namespace Tests\Feature;

use App\Models\CaseParty;
use App\Models\Client;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseSideAndTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_side_court_room_and_vc_link_are_saved_with_the_case(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cases.store'), $this->payload([
            'appearing_for' => 'respondent',
            'court_room' => 'Court No. 5',
            'vc_link' => 'https://vc.example.in/court5',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('legal_cases', [
            'case_title' => 'Asha vs State',
            'appearing_for' => 'respondent',
            'court_room' => 'Court No. 5',
            'vc_link' => 'https://vc.example.in/court5',
        ]);
    }

    public function test_side_must_be_a_known_side_and_vc_link_a_web_address(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cases.store'), $this->payload([
            'appearing_for' => 'judge',
            'vc_link' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['appearing_for', 'vc_link']);

        $this->assertDatabaseCount('legal_cases', 0);
    }

    public function test_case_page_shows_side_room_vc_link_and_client_contact_up_front(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create($this->payload([
            'appearing_for' => 'petitioner',
            'court_room' => 'Room 214',
            'vc_link' => 'https://vc.example.in/room214',
        ]));
        $client = Client::create([
            'client_type' => 'individual',
            'first_name' => 'Ravi',
            'display_name' => 'Ravi Kumar',
            'mobile' => '98765 43210',
            'country' => 'India',
            'status' => 'active',
        ]);
        CaseParty::create(['case_id' => $case->id, 'client_id' => $client->id, 'party_name' => 'Ravi Kumar', 'is_client' => true]);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSeeInOrder(['For', 'Petitioner', 'Room 214', 'Case workspace', 'Client link', 'Notify clients'])
            ->assertSee('href="https://vc.example.in/room214"', false)
            ->assertSee('Join VC')
            ->assertSee('href="tel:+919876543210"', false)
            ->assertSee('https://wa.me/919876543210?text=', false)
            ->assertSee('data-share-text=', false);
    }

    public function test_hearing_room_is_preferred_over_the_case_room(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create($this->payload(['court_room' => 'Room 1']));
        CourtHearing::create([
            'case_id' => $case->id,
            'hearing_date' => today()->subDay(),
            'next_hearing_date' => today()->addDays(3),
            'court_room' => 'Court 12',
        ]);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSeeInOrder(['Next hearing', 'Court 12']);
    }

    public function test_a_hearing_can_carry_its_own_vc_link_which_beats_the_case_link(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create($this->payload(['vc_link' => 'https://vc.example.in/usual']));
        $date = today()->addDays(2)->toDateString();

        $this->post(route('hearings.store'), [
            'case_id' => $case->id,
            'hearing_date' => $date,
            'vc_link' => 'https://vc.example.in/this-date',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('court_hearings', ['case_id' => $case->id, 'vc_link' => 'https://vc.example.in/this-date']);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('href="https://vc.example.in/this-date"', false)
            ->assertDontSee('Join VC for this hearing')
            ->assertSee('data-copy="https://vc.example.in/this-date"', false);
    }

    public function test_hearing_vc_link_must_be_a_web_address(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create($this->payload());

        $this->post(route('hearings.store'), [
            'case_id' => $case->id,
            'hearing_date' => today()->toDateString(),
            'vc_link' => 'not a link',
        ])->assertSessionHasErrors('vc_link');
    }

    public function test_case_page_sections_are_tabs_with_one_panel_each(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create($this->payload());

        $html = $this->get(route('cases.show', $case))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'data-tabs'));

        foreach (['case-overview', 'case-history', 'case-clients', 'case-notes', 'case-appointments', 'case-documents', 'case-expenses', 'case-time'] as $panel) {
            $this->assertStringContainsString('data-tab="'.$panel.'"', $html);
            $this->assertSame(1, preg_match_all('/id="'.$panel.'" data-tab-panel/', $html), "$panel should be one tab panel");
        }
    }

    public function test_case_list_shows_which_side_we_appear_for(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create($this->payload(['appearing_for' => 'accused']));

        $this->get(route('cases.index'))
            ->assertOk()
            ->assertSee('We appear for the accused', false);
    }

    public function test_case_list_shows_room_judge_and_vc_link_with_the_court(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create($this->payload([
            'court_name' => 'Saket Courts',
            'court_room' => '012, Block-I',
            'judge_name' => 'Sh. Rakesh Pandit',
            'vc_link' => 'https://districtcourtdelhi.webex.com/meet/ddc.vc.south11',
        ]));

        $this->get(route('cases.index'))
            ->assertOk()
            ->assertSeeInOrder(['Saket Courts', 'Room 012, Block-I', 'Sh. Rakesh Pandit', 'Join VC'])
            ->assertSee('href="https://districtcourtdelhi.webex.com/meet/ddc.vc.south11"', false);
    }

    public function test_client_page_uses_tabs_and_offers_call_and_whatsapp(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create([
            'client_type' => 'individual',
            'first_name' => 'Meera',
            'display_name' => 'Meera Rao',
            'mobile' => '9123456780',
            'country' => 'India',
            'status' => 'active',
        ]);

        $this->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('data-tabs', false)
            ->assertSee('id="activities" data-tab-panel', false)
            ->assertSee('href="tel:+919123456780"', false)
            ->assertSee('href="https://wa.me/919123456780"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'case_title' => 'Asha vs State',
            'court_name' => 'District Court Saket',
            'case_status' => 'pending',
        ], $overrides);
    }
}
