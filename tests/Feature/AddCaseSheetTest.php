<?php

namespace Tests\Feature;

use App\Models\JudicialDistrict;
use App\Models\LegalCase;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddCaseSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_case_lists_every_court_level_and_the_other_ways_to_add(): void
    {
        $this->actingAs(User::factory()->create());
        $state = State::query()->firstOrCreate(['code' => 'IN-UP'], ['name' => 'Uttar Pradesh', 'country_code' => 'IN', 'is_active' => true]);
        JudicialDistrict::query()->firstOrCreate(['state_id' => $state->id, 'district_name' => 'Lucknow'], ['is_active' => true]);
        LegalCase::create(['case_title' => 'Asha v. State', 'court_name' => 'Civil Judge (S.D.)', 'court_level' => 'district', 'state_code' => 'UP', 'district' => 'Jalaun']);

        $this->get(route('sheets.add-case'))->assertOk()
            ->assertSeeInOrder(['Your courts', 'District Court Jalaun', 'All courts', 'Supreme Court of India', 'Allahabad High Court', 'District Court Lucknow'])
            ->assertSee(e(route('cases.find', ['method' => 'website', 'level' => 'high_court', 'court_name' => 'Allahabad High Court'])), false)
            ->assertSee('CNR number search')->assertSee('Import from eCourts app')->assertSee('Add case manually');
    }

    public function test_a_court_picked_in_add_case_arrives_filled_in(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('cases.find', ['method' => 'website', 'level' => 'high_court', 'court_name' => 'Allahabad High Court']))->assertOk()
            ->assertSee('value="high_court" data-switch="level" checked', false)
            ->assertSee('value="Allahabad High Court"', false);
        $this->get(route('cases.find', ['level' => 'nonsense']))->assertOk()
            ->assertSee('value="district" data-switch="level" checked', false);
    }

    public function test_each_court_level_offers_its_own_ways_to_search(): void
    {
        $this->actingAs(User::factory()->create());
        $court = 'Principal Judge Family Court, South West';

        $this->get(route('sheets.add-case.method', ['level' => 'district', 'court' => $court]))->assertOk()
            ->assertSee($court)->assertSeeInOrder(['Party name', 'Case number', 'Filing number', 'FIR number', 'Bar code', 'Advocate name'])
            ->assertDontSee('Diary number')
            ->assertSee('data-sheet="'.e(route('sheets.add-case.search', ['method' => 'fir', 'level' => 'district', 'court' => $court])).'"', false);

        $this->get(route('sheets.add-case.method', ['level' => 'supreme_court', 'court' => 'Supreme Court of India']))->assertOk()
            ->assertSee('Diary number')->assertDontSee('FIR number');
        $this->get(route('sheets.add-case.search', ['method' => 'fir', 'level' => 'supreme_court', 'court' => 'Supreme Court of India']))->assertNotFound();
        $this->get(route('sheets.add-case.method', ['level' => 'moon', 'court' => 'x']))->assertRedirect();
    }

    public function test_search_details_lead_to_ecourts_with_a_paste_box(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Asha Sharma']));
        $court = ['level' => 'district', 'court' => 'District Court Lucknow'];

        $this->get(route('sheets.add-case.search', ['method' => 'case_number'] + $court))->assertOk()
            ->assertSee('Search by case number')->assertSee('name="case_type"', false)->assertSee('name="year" required', false)
            ->assertSee('data-sheet-step', false);
        $this->get(route('sheets.add-case.search', ['method' => 'advocate'] + $court))->assertOk()
            ->assertSee('value="Asha Sharma"', false);

        $this->get(route('sheets.add-case.continue', ['method' => 'case_number'] + $court + ['case_type' => 'HMA', 'case_number' => '2424', 'year' => '2024']))->assertOk()
            ->assertSeeInOrder(['District Court Lucknow', 'Case type', 'HMA', 'Case number', '2424', 'Case year', '2024'])
            ->assertSee('https://services.ecourts.gov.in/ecourtindia_v6/', false)
            ->assertSee('action="'.route('cases.find.preview').'"', false)->assertSee('name="results"', false);
        $this->get(route('sheets.add-case.continue', ['method' => 'case_number'] + $court + ['case_type' => 'HMA']))
            ->assertSessionHasErrors(['case_number', 'year']);
    }
}
