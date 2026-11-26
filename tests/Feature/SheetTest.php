<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    public function test_quick_add_sheets_save_through_the_normal_forms(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);

        foreach ([
            route('sheets.next-date', ['case' => $case->id]) => route('hearings.store'),
            route('sheets.next-date') => route('hearings.store'),
            route('sheets.task') => route('tasks.store'),
            route('sheets.client') => route('clients.store'),
            route('sheets.case') => route('cases.store'),
        ] as $sheet => $action) {
            $this->get($sheet)
                ->assertOk()
                ->assertSee('data-sheet-heading', false)
                ->assertSee('action="'.$action.'"', false)
                ->assertSee('data-sheet-form', false)
                ->assertDontSee('<html', false);
        }

        // From the sheet, a missing field comes back as errors to show beside the field.
        $this->postJson(route('hearings.store'), ['case_id' => $case->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hearing_date');

        $this->postJson(route('tasks.store'), ['title' => 'File reply', 'priority' => 'medium', 'status' => 'pending', 'case_id' => $case->id]);
        $this->assertDatabaseHas('tasks', ['title' => 'File reply', 'case_id' => $case->id]);
    }

    public function test_case_client_court_and_payment_sheets_show_the_right_details(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['client_code' => Client::nextCode(), 'client_type' => 'individual', 'first_name' => 'Ravi', 'last_name' => 'Kumar', 'mobile' => '9876543210', 'status' => 'active']);
        $case = LegalCase::create(['case_title' => 'Ravi vs State', 'case_number' => 'CS/1/2026', 'court_name' => 'Saket Courts', 'case_status' => 'pending', 'appearing_for' => 'petitioner']);
        $case->parties()->create(['client_id' => $client->id, 'party_name' => 'Ravi Kumar', 'is_client' => true]);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-10', 'hearing_time' => '10:30:00', 'hearing_stage' => 'Arguments']);

        $this->get(route('sheets.case-view', $case))
            ->assertOk()
            ->assertSeeInOrder(['Ravi vs State', 'Next date', 'Today · 10:30', 'Arguments', 'Ravi Kumar', 'WhatsApp', 'Open case'])
            ->assertSee(route('sheets.next-date', ['case' => $case->id]), false);

        $this->get(route('sheets.client-card', $client))
            ->assertOk()
            ->assertSee('href="tel:9876543210"', false)
            ->assertSee('Send hearing update')
            ->assertSee('Ravi vs State');

        $bench = CourtBench::create(['court_key' => 'delhi-district-south', 'court_room' => '031', 'judges' => [['name' => 'Ms. Shreya Arora Mehta', 'designation' => 'District Judge-04']], 'vc_link' => 'https://vc.example/south14', 'vc_meeting_id' => '176 111 2222', 'email' => 'adj04-south@ddc.nic.in']);
        $this->get(route('sheets.court', ['bench' => $bench->id]))
            ->assertOk()
            ->assertSeeInOrder(['Room 031', 'Ms. Shreya Arora Mehta', 'District Judge-04', '176 111 2222', 'adj04-south@ddc.nic.in', 'Join VC'])
            ->assertSee('data-copy="1761112222"', false)
            ->assertSee(route('courts.delhi-district-courts.judges', 'south'), false);

        $invoice = Invoice::create(['client_id' => $client->id, 'invoice_number' => 'INV-9', 'invoice_date' => '2026-03-01', 'due_date' => '2026-03-31', 'subtotal' => 5000, 'total_amount' => 5000, 'status' => 'sent']);
        $this->get(route('sheets.payment', $invoice))
            ->assertOk()
            ->assertSee('INV-9')
            ->assertSee('value="5000.00"', false)
            ->assertSee('action="'.route('payments.store').'"', false);
    }

    public function test_another_accounts_records_do_not_open_in_a_sheet(): void
    {
        $this->actingAs(User::factory()->create());
        $theirCase = LegalCase::create(['case_title' => 'Private matter', 'court_name' => 'Saket', 'case_status' => 'pending']);

        $this->actingAs(User::factory()->create());
        $this->get(route('sheets.case-view', $theirCase))->assertNotFound();
        $this->get(route('sheets.next-date', ['case' => $theirCase->id]))->assertNotFound();
    }

    public function test_search_sheet_finds_cases_clients_and_court_rooms_as_you_type(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'Mehta vs Union', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);
        Client::create(['client_code' => Client::nextCode(), 'client_type' => 'individual', 'first_name' => 'Anil', 'last_name' => 'Mehta', 'status' => 'active']);
        CourtBench::create(['court_key' => 'delhi-district-south', 'court_room' => '031', 'judges' => [['name' => 'Ms. Shreya Arora Mehta']]]);

        $this->get(route('sheets.search', ['q' => 'mehta']))
            ->assertOk()
            ->assertSeeInOrder(['Cases', 'Mehta vs Union', 'Clients', 'Anil Mehta', 'Court rooms', 'Ms. Shreya Arora Mehta', 'All results for'])
            ->assertDontSee('<html', false);

        $this->get(route('sheets.search', ['q' => 'm']))->assertSee('Type a case, party, number');
    }

    public function test_pages_open_inside_the_sheet_without_the_menu_and_chips_open_sheets(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('court-fee.index', ['court' => 'high', 'embed' => 1]))
            ->assertOk()
            ->assertSee('class="min-h-screen vn-embed"', false);

        $case = LegalCase::create(['case_title' => 'Ravi vs State', 'court_name' => 'Saket Courts', 'court_room' => '12', 'judge_name' => 'Sh. A Kumar', 'case_status' => 'pending']);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee(route('sheets.court', ['court' => 'Saket Courts', 'designation' => 'Saket Courts', 'room' => '12', 'judge' => 'Sh. A Kumar']))
            ->assertDontSee(route('court-fee.index', ['court' => 'district']));

        $this->get(route('dashboard'))->assertSee('name="vn-search-sheet"', false);
    }
}
