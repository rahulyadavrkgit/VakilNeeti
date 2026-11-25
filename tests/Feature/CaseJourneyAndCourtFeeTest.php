<?php

namespace Tests\Feature;

use App\Models\CaseLink;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaseJourneyAndCourtFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_matter_is_followed_from_district_court_to_high_court_to_supreme_court(): void
    {
        $this->actingAs(User::factory()->create());
        $district = $this->makeCase('Asha vs State', 'District Court Saket', 'CS/1/2024');
        $highCourt = $this->makeCase('Asha vs State', 'High Court of Delhi', 'RFA/9/2025');
        $supreme = $this->makeCase('Asha vs State', 'Supreme Court of India', 'SLP(C)/4/2026');

        $this->post(route('cases.links.store', $highCourt), ['linked_case_id' => $district->id, 'link_type' => 'lower'])
            ->assertRedirect(route('cases.show', $highCourt).'#case-journey');
        $this->post(route('cases.links.store', $highCourt), ['linked_case_id' => $supreme->id, 'link_type' => 'higher']);

        $this->assertDatabaseHas('case_links', ['case_id' => $highCourt->id, 'linked_case_id' => $district->id, 'relation' => 'arises_from']);
        $this->assertDatabaseHas('case_links', ['case_id' => $supreme->id, 'linked_case_id' => $highCourt->id, 'relation' => 'arises_from']);

        // Seen from the District Court case, the whole chain appears in order.
        $this->get(route('cases.show', $district))
            ->assertOk()
            ->assertSeeInOrder(['Case journey', 'Supreme Court', 'Now here', 'SLP(C)/4/2026', 'Delhi HC', 'RFA/9/2025', 'District Court', 'CS/1/2024']);

        // One matter number for the whole chain: the district case's.
        $this->assertSame([$district->matter_id], LegalCase::query()->pluck('matter_id')->unique()->values()->all());
    }

    public function test_connected_matters_are_listed_and_can_be_unlinked(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase('Asha vs State', 'District Court Saket', 'CS/1/2024');
        $other = $this->makeCase('Asha vs Ravi', 'District Court Saket', 'CS/2/2024');

        $this->post(route('cases.links.store', $case), ['linked_case_id' => $other->id, 'link_type' => 'connected']);
        $link = CaseLink::firstOrFail();

        $this->get(route('cases.show', $other))->assertSeeInOrder(['Connected matters', 'CS/1/2024']);

        $this->delete(route('cases.links.destroy', [$other, $link]))->assertSessionHas('success', 'Link removed.');
        $this->assertDatabaseCount('case_links', 0);
    }

    public function test_cases_are_linked_only_once_and_never_to_themselves(): void
    {
        $this->actingAs(User::factory()->create());
        $case = $this->makeCase('Asha vs State', 'District Court Saket', 'CS/1/2024');
        $other = $this->makeCase('Asha vs Ravi', 'District Court Saket', 'CS/2/2024');

        $this->post(route('cases.links.store', $case), ['linked_case_id' => $other->id, 'link_type' => 'connected']);
        $this->post(route('cases.links.store', $other), ['linked_case_id' => $case->id, 'link_type' => 'lower'])
            ->assertSessionHas('success', 'These cases are already linked.');
        $this->post(route('cases.links.store', $case), ['linked_case_id' => $case->id, 'link_type' => 'connected'])
            ->assertSessionHasErrors('linked_case_id');

        $this->assertDatabaseCount('case_links', 1);
    }

    public function test_another_advocates_case_cannot_be_linked_or_unlinked(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $theirs = $this->makeCase('Their case', 'District Court Saket', 'CS/7/2024');
        $theirOther = $this->makeCase('Their other case', 'District Court Saket', 'CS/8/2024');
        $theirLink = CaseLink::create(['case_id' => $theirs->id, 'linked_case_id' => $theirOther->id, 'relation' => 'connected']);

        $this->actingAs(User::factory()->create());
        $mine = $this->makeCase('My case', 'District Court Saket', 'CS/9/2024');

        $this->post(route('cases.links.store', $mine), ['linked_case_id' => $theirs->id, 'link_type' => 'connected'])->assertNotFound();
        $this->delete(route('cases.links.destroy', [$mine, $theirLink]))->assertNotFound();
        $this->assertDatabaseCount('case_links', 1);
    }

    public function test_where_to_stand_is_recorded_once_per_court_and_follows_our_side(): void
    {
        $this->actingAs(User::factory()->create());
        $forPetitioner = $this->makeCase('A vs B', 'District Court Saket', 'CS/1/2024', ['appearing_for' => 'petitioner']);
        $forRespondent = $this->makeCase('C vs D', 'District Court Saket', 'CS/2/2024', ['appearing_for' => 'respondent']);

        $this->get(route('cases.show', $forPetitioner))->assertSee('where does the petitioner', false);

        $this->post(route('cases.stand-side', $forPetitioner), ['petitioner_side' => 'left'])
            ->assertSessionHas('success', 'Saved for every case in District Court Saket.');

        $this->get(route('cases.show', $forPetitioner))->assertSeeInOrder(['YOUR LEFT', 'the judge&#039;s right'], false);
        $this->get(route('cases.show', $forRespondent))->assertSeeInOrder(['YOUR RIGHT', 'the judge&#039;s left'], false);

        $this->post(route('cases.stand-side', $forPetitioner), ['petitioner_side' => 'centre'])->assertSessionHasErrors('petitioner_side');
    }

    public function test_more_than_one_party_on_our_side_is_shown_with_the_side(): void
    {
        $this->assertSame('Respondent No. 2', LegalCase::sideLabel('respondent', '2'));
        $this->assertSame('Respondent Nos. 2, 3', LegalCase::sideLabel('respondent', '2, 3'));
        $this->assertSame('Petitioner Nos. 1-4', LegalCase::sideLabel('petitioner', '1-4'));
        $this->assertSame('Accused (Ram Kumar)', LegalCase::sideLabel('accused', 'Ram Kumar'));
        $this->assertSame('Respondent', LegalCase::sideLabel('respondent', '  '));

        $this->actingAs(User::factory()->create());
        $this->post(route('cases.store'), [
            'case_title' => 'A vs B',
            'court_name' => 'District Court Saket',
            'case_status' => 'pending',
            'appearing_for' => 'respondent',
            'appearing_for_parties' => '2, 3',
        ])->assertSessionHasNoErrors();

        $this->get(route('cases.index'))->assertSee('Respondent Nos. 2, 3');
    }

    public function test_court_fee_shows_the_supreme_court_schedule_and_filing(): void
    {
        $this->get(route('court-fee.index', ['court' => 'supreme']))
            ->assertOk()
            ->assertSee('Supreme Court Rules, 2013')
            ->assertSee('href="https://efiling.sci.gov.in/"', false)
            ->assertDontSee('Choose State / UT');
    }

    public function test_court_fee_for_a_state_names_its_high_court_and_links_its_law_and_payment(): void
    {
        $this->get(route('court-fee.index', ['court' => 'high', 'state' => 'Haryana']))
            ->assertOk()
            ->assertSee('<title>Court fee: Punjab and Haryana High Court (Haryana) | VakilNiti</title>', false)
            ->assertSee('href="https://indiacode.gov.in/search?query=Haryana%20court%20fees"', false)
            ->assertSee('href="https://pay.ecourts.gov.in/epay/"', false);

        $this->get(route('court-fee.index', ['court' => 'district', 'state' => 'Kerala']))
            ->assertOk()
            ->assertSee('District Courts of Kerala');
    }

    public function test_court_fee_asks_for_a_state_and_ignores_unknown_values(): void
    {
        $this->get(route('court-fee.index', ['court' => 'tribunal', 'state' => 'Atlantis']))
            ->assertOk()
            ->assertSee('Choose a State to see its court fee law and payment links.');
    }

    public function test_court_fee_starts_with_the_advocates_practice_state(): void
    {
        $user = User::factory()->create();
        DB::table('firm_practice_states')->insert([
            'user_id' => $user->id,
            'state_name' => 'Uttar Pradesh',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('court-fee.index'))
            ->assertOk()
            ->assertSee('District Courts of Uttar Pradesh');
    }

    public function test_embed_check_reports_which_official_sites_may_be_shown_inside_vakilniti(): void
    {
        Http::fake([
            'https://lucknow.dcourts.gov.in/' => Http::response('', 200, ['X-Frame-Options' => 'SAMEORIGIN']),
            'https://open.gov.in/' => Http::response('', 200),
        ]);

        $this->getJson(route('embed-check', ['hosts' => ['lucknow.dcourts.gov.in', 'open.gov.in', 'example.com']]))
            ->assertOk()
            ->assertExactJson(['lucknow.dcourts.gov.in' => false, 'open.gov.in' => true]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeCase(string $title, string $court, string $number, array $extra = []): LegalCase
    {
        return LegalCase::create(array_merge([
            'case_title' => $title,
            'court_name' => $court,
            'case_number' => $number,
            'case_status' => 'pending',
        ], $extra));
    }
}
