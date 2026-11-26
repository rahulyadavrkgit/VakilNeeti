<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseClientsAndDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_the_party_we_appear_for_becomes_the_client_by_itself(): void
    {
        $this->post(route('cases.store'), $this->case(['case_title' => 'Ram Kumar & Ors vs State of UP', 'appearing_for' => 'petitioner']))->assertRedirect();
        $this->post(route('cases.store'), $this->case(['case_title' => 'State vs Mohan Lal', 'case_number' => 'SC/2/2026', 'appearing_for' => 'accused']))->assertRedirect();

        $this->assertSame(['Mohan Lal', 'Ram Kumar'], Client::query()->orderBy('display_name')->pluck('display_name')->all());
        $ram = LegalCase::query()->where('case_title', 'like', 'Ram Kumar%')->firstOrFail();
        $this->assertTrue($ram->parties()->where('is_client', true)->where('party_name', 'Ram Kumar')->exists());
    }

    public function test_a_named_side_an_existing_client_and_an_unclear_side_are_handled(): void
    {
        $asha = Client::create(['client_code' => Client::nextCode(), 'client_type' => 'individual', 'first_name' => 'Asha Devi', 'display_name' => 'Asha Devi', 'country' => 'India', 'status' => 'active']);

        // "Respondent (Asha Devi)": the typed name wins, and the existing client is reused.
        $this->post(route('cases.store'), $this->case(['case_title' => 'Bank vs Asha Devi and others', 'appearing_for' => 'respondent', 'appearing_for_parties' => 'asha devi']))->assertRedirect();
        // Party numbers say which, not who: the title gives the name.
        $this->post(route('cases.store'), $this->case(['case_title' => 'Gopal vs Hari', 'case_number' => 'CS/3/2026', 'appearing_for' => 'defendant', 'appearing_for_parties' => '2']))->assertRedirect();
        // No side, or an intervenor: nobody is guessed.
        $this->post(route('cases.store'), $this->case(['case_title' => 'Meera vs Sohan', 'case_number' => 'CS/4/2026']))->assertRedirect();
        $this->post(route('cases.store'), $this->case(['case_title' => 'Lata vs Union', 'case_number' => 'CS/5/2026', 'appearing_for' => 'intervenor']))->assertRedirect();

        $this->assertSame(['Asha Devi', 'Hari'], Client::query()->orderBy('display_name')->pluck('display_name')->all());
        $this->assertSame(1, $asha->caseParties()->count());

        // Setting the side later links the client then, once.
        $meera = LegalCase::query()->where('case_title', 'Meera vs Sohan')->firstOrFail();
        $this->put(route('cases.update', $meera), $this->case(['case_title' => 'Meera vs Sohan', 'case_number' => 'CS/4/2026', 'appearing_for' => 'plaintiff']))->assertRedirect();
        $this->put(route('cases.update', $meera), $this->case(['case_title' => 'Meera vs Sohan', 'case_number' => 'CS/4/2026', 'appearing_for' => 'defendant']))->assertRedirect();
        $this->assertSame(1, $meera->parties()->where('is_client', true)->count());
        $this->assertSame('Meera', $meera->parties()->where('is_client', true)->value('party_name'));
    }

    public function test_a_case_already_in_the_diary_opens_instead_of_being_added_again(): void
    {
        $this->post(route('cases.store'), $this->case(['cnr_number' => 'DLHC010170852024']))->assertRedirect();
        $first = LegalCase::query()->firstOrFail();

        // Same CNR, written differently.
        $this->post(route('cases.store'), $this->case(['case_title' => 'Typed again', 'case_number' => 'OTHER/1/2026', 'cnr_number' => 'dlhc 0101 7085 2024']))
            ->assertRedirect(route('cases.show', $first))
            ->assertSessionHas('success', 'This case is already in your diary, so it was not added again.');

        // Same number in the same court, spaces and letter case aside.
        $this->post(route('cases.store'), $this->case(['case_title' => 'Typed again', 'case_number' => 'cs / 1 / 2026']))->assertRedirect(route('cases.show', $first));

        // The same number in another court is a different case.
        $this->post(route('cases.store'), $this->case(['court_name' => 'Tis Hazari Courts']))->assertRedirect();
        $this->assertSame(2, LegalCase::query()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function case(array $overrides = []): array
    {
        return array_merge([
            'case_title' => 'Asha vs State',
            'case_number' => 'CS/1/2026',
            'court_name' => 'Saket Courts',
            'case_status' => 'pending',
        ], $overrides);
    }
}
