<?php

namespace Tests\Feature;

use App\Models\CourtCode;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourtIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The official judicial districts the court complex text is checked against.
        foreach (['Uttar Pradesh' => ['IN-UP', ['Jalaun', 'Ghaziabad', 'Gautam Buddha Nagar']], 'Delhi' => ['IN-DL', ['Central', 'New Delhi', 'South East']]] as $state => [$code, $districts]) {
            $stateId = DB::table('states')->insertGetId(['name' => $state, 'code' => $code, 'country_code' => 'IN', 'is_union_territory' => $code === 'IN-DL', 'is_active' => true, 'sort_order' => 1]);

            foreach ($districts as $district) {
                DB::table('judicial_districts')->insert(['state_id' => $stateId, 'district_name' => $district, 'is_active' => true]);
            }
        }

        $this->actingAs(User::factory()->create());
    }

    public function test_the_cnr_tells_the_state_court_and_district_and_the_code_is_remembered(): void
    {
        $jalaun = LegalCase::create(['case_title' => 'Ram vs Shyam', 'cnr_number' => 'UPJL010012342023', 'court_name' => 'Chief Judicial Magistrate',
            'court_complex' => 'Chief Judicial Magistrate, Jalaun, Uttar Pradesh', 'case_status' => 'pending']);

        $this->assertSame(['UP', 'district', 'UPJL01', 'Jalaun'], [$jalaun->state_code, $jalaun->court_level, $jalaun->court_code, $jalaun->district]);
        $this->assertSame('Jalaun, UP', $jalaun->courtLabel());
        $this->assertSame('Jalaun', CourtCode::where('code', 'UPJL')->value('district'));

        // Another Jalaun case without the complex text still gets the district from the remembered code.
        $second = LegalCase::create(['case_title' => 'A vs B', 'cnr_number' => 'UPJL050000012024', 'court_name' => 'Civil Judge (J.D.)', 'case_status' => 'pending']);
        $this->assertSame('Jalaun, UP', $second->courtLabel());

        // eCourts writes the district last: Rouse Avenue is in Central, not New Delhi.
        $rouse = LegalCase::create(['case_title' => 'CBI vs X', 'cnr_number' => 'DLCT120000012022', 'court_name' => 'Special Judge',
            'court_complex' => 'Chief Metropolitan Magistrate, Rouse Avenue, New Delhi, Central', 'case_status' => 'pending']);
        $this->assertSame('Central, Delhi', $rouse->courtLabel());

        $highCourt = LegalCase::create(['case_title' => 'X vs Union', 'cnr_number' => 'DLHC010000012024', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        $this->assertSame(['DL', 'high', 'Delhi HC'], [$highCourt->state_code, $highCourt->court_level, $highCourt->courtLabel()]);

        // Every case is its own matter until linked.
        $this->assertSame(['VN-0001', 'VN-0002', 'VN-0003', 'VN-0004'], LegalCase::orderBy('id')->pluck('matter_id')->all());
    }

    public function test_without_a_cnr_the_court_is_asked_step_by_step(): void
    {
        $this->post(route('cases.store'), ['case_title' => 'Rajendra Meena vs State of RJ', 'case_number' => 'SLP(C)/28407/2026', 'case_status' => 'pending',
            'court_level' => 'supreme', 'state_code' => 'UP', 'district' => 'Jalaun', 'diary_number' => '47715/2026', 'court_name' => ''])->assertRedirect();

        $supreme = LegalCase::where('case_title', 'Rajendra Meena vs State of RJ')->firstOrFail();
        $this->assertSame(['Supreme Court of India', 'SC', null, null, '47715/2026'], [$supreme->court_name, $supreme->court_code, $supreme->state_code, $supreme->district, $supreme->diary_number]);

        $this->post(route('cases.store'), ['case_title' => 'Ram vs State', 'case_status' => 'pending',
            'court_level' => 'high', 'state_code' => 'UP', 'court_name' => ''])->assertRedirect();
        $this->assertSame('Allahabad High Court', LegalCase::where('case_title', 'Ram vs State')->value('court_name'));

        $this->post(route('cases.store'), ['case_title' => 'Gupta vs Gupta', 'case_status' => 'pending',
            'court_level' => 'district', 'state_code' => 'UP', 'district' => 'Ghaziabad', 'court_name' => 'Family Court'])->assertRedirect();
        $this->assertSame('Ghaziabad, UP', LegalCase::where('case_title', 'Gupta vs Gupta')->firstOrFail()->courtLabel());

        // A district court still needs its bench.
        $this->post(route('cases.store'), ['case_title' => 'No bench', 'case_status' => 'pending', 'court_level' => 'district', 'state_code' => 'UP', 'district' => 'Jalaun'])
            ->assertSessionHasErrors('court_name');
    }

    public function test_cases_can_be_filtered_by_court_and_the_picker_offers_my_courts(): void
    {
        LegalCase::create(['case_title' => 'Jalaun matter', 'cnr_number' => 'UPJL010012342023', 'court_name' => 'CJM', 'court_complex' => 'CJM, Jalaun, Uttar Pradesh', 'case_status' => 'pending']);
        LegalCase::create(['case_title' => 'Delhi HC matter', 'cnr_number' => 'DLHC010000012024', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);

        $this->get(route('cases.index'))->assertOk()->assertSee('All courts')->assertSeeInOrder(['Delhi HC', 'Jalaun, UP']);
        $this->get(route('cases.index', ['court' => 'UPJL']))->assertOk()->assertSee('Jalaun matter')->assertDontSee('Delhi HC matter');

        $this->get(route('cases.manual'))->assertOk()->assertSee('Your courts')->assertSee('data-court-choice', false)->assertSee('Court level');
        $this->getJson(route('api.locations.court-districts', 'UP'))->assertOk()->assertJson(['data' => ['Gautam Buddha Nagar', 'Ghaziabad', 'Jalaun']]);
    }
}
