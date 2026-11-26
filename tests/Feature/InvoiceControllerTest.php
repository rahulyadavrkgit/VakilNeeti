<?php

namespace Tests\Feature;

use App\Models\CaseExpense;
use App\Models\CaseTimeEntry;
use App\Models\Client;
use App\Models\FirmProfile;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_invoice_with_line_items_gst_and_an_auto_number(): void
    {
        $client = $this->actingAsUserWithClient();

        $response = $this->post(route('invoices.store'), $this->payload($client, [
            'items' => [
                ['description' => 'Appearance fee', 'quantity' => 2, 'rate' => 5000, 'tax_rate' => 18, 'tax_mode' => 'exclusive'],
                ['description' => 'Court fee', 'quantity' => 1, 'rate' => 500, 'tax_rate' => 0, 'tax_mode' => 'exclusive'],
            ],
            'discount' => 0,
        ]));

        $invoice = Invoice::with('items')->firstOrFail();
        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('INV/'.now()->year.'/0001', $invoice->invoice_number);
        $this->assertSame('10500.00', $invoice->subtotal);
        $this->assertSame('1800.00', $invoice->tax_amount);
        $this->assertSame('12300.00', $invoice->total_amount);
        $this->assertCount(2, $invoice->items);
        $this->assertSame('11800.00', $invoice->items[0]->line_total);
    }

    public function test_numbers_follow_the_saved_format_and_continue_the_sequence(): void
    {
        $client = $this->actingAsUserWithClient();
        $firm = FirmProfile::create(['firm_name' => 'Sharma Legal', 'is_active' => true]);
        DB::table('invoice_settings')->insert([
            'user_id' => auth()->id(),
            'firm_profile_id' => $firm->id,
            'invoice_prefix' => 'VN',
            'invoice_number_format' => '{PREFIX}-{YEAR}{MONTH}-{NUMBER}',
            'default_payment_due_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('invoices.store'), $this->payload($client));
        $this->post(route('invoices.store'), $this->payload($client));

        $this->assertSame(
            ['VN-'.now()->format('Ym').'-0001', 'VN-'.now()->format('Ym').'-0002'],
            Invoice::orderBy('id')->pluck('invoice_number')->all()
        );
    }

    public function test_two_users_can_each_have_invoice_number_one(): void
    {
        $this->post(route('invoices.store'), $this->payload($this->actingAsUserWithClient()));
        $this->post(route('invoices.store'), $this->payload($this->actingAsUserWithClient()));

        $this->assertSame(2, Invoice::withoutGlobalScopes()->where('invoice_number', 'INV/'.now()->year.'/0001')->count());
    }

    public function test_payment_terms_set_the_due_date(): void
    {
        $client = $this->actingAsUserWithClient();

        $this->post(route('invoices.store'), $this->payload($client, ['invoice_date' => '2026-11-01', 'payment_terms' => 'net_15']));

        $this->assertSame('2026-11-16', Invoice::firstOrFail()->due_date->toDateString());
    }

    public function test_custom_terms_need_a_due_date(): void
    {
        $client = $this->actingAsUserWithClient();

        $this->post(route('invoices.store'), $this->payload($client, ['payment_terms' => 'custom', 'due_date' => null]))
            ->assertSessionHasErrors('due_date');
    }

    public function test_cannot_invoice_another_users_client(): void
    {
        $otherClient = $this->actingAsUserWithClient();
        $this->actingAsUserWithClient();

        $this->post(route('invoices.store'), $this->payload($otherClient))
            ->assertSessionHasErrors(['client_id' => 'Choose one of your clients.']);
    }

    public function test_rejects_invalid_pan_and_gstin(): void
    {
        $client = $this->actingAsUserWithClient();

        $this->post(route('invoices.store'), $this->payload($client, ['client_pan' => 'ABC123', 'client_gstin' => 'NOTAGSTIN']))
            ->assertSessionHasErrors([
                'client_pan' => 'Enter a valid PAN, e.g. ABCDE1234F.',
                'client_gstin' => 'Enter a valid 15-character GSTIN.',
            ]);
    }

    public function test_billing_case_expenses_and_time_marks_them_billed(): void
    {
        $client = $this->actingAsUserWithClient();
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Court', 'case_status' => 'pending']);
        $expense = CaseExpense::create(['case_id' => $case->id, 'description' => 'Typing', 'amount' => 300, 'expense_date' => '2026-11-10']);
        $time = CaseTimeEntry::create(['case_id' => $case->id, 'description' => 'Drafting', 'entry_date' => '2026-11-10', 'hours' => 1, 'minutes' => 30, 'hourly_rate' => 2000]);

        $billables = $this->getJson(route('cases.billables', $case))->assertOk()->json('data');
        $this->assertCount(2, $billables);
        $this->assertSame(1.5, $billables[1]['quantity']);

        $this->post(route('invoices.store'), $this->payload($client, [
            'legal_case_id' => $case->id,
            'items' => [
                ['description' => $billables[0]['description'], 'quantity' => 1, 'rate' => 300, 'source_type' => 'expense', 'source_id' => $expense->id],
                ['description' => $billables[1]['description'], 'quantity' => 1.5, 'rate' => 2000, 'source_type' => 'time', 'source_id' => $time->id],
            ],
        ]))->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertSame($invoice->id, $expense->fresh()->invoice_id);
        $this->assertSame($invoice->id, $time->fresh()->invoice_id);
        $this->assertSame([], $this->getJson(route('cases.billables', $case))->json('data'));
    }

    public function test_deleting_an_invoice_releases_its_billed_expenses(): void
    {
        $client = $this->actingAsUserWithClient();
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'Court', 'case_status' => 'pending']);
        $expense = CaseExpense::create(['case_id' => $case->id, 'description' => 'Typing', 'amount' => 300, 'expense_date' => '2026-11-10']);
        $this->post(route('invoices.store'), $this->payload($client, [
            'items' => [['description' => 'Typing', 'quantity' => 1, 'rate' => 300, 'source_type' => 'expense', 'source_id' => $expense->id]],
        ]));

        $this->delete(route('invoices.destroy', Invoice::firstOrFail()));

        $this->assertNull($expense->fresh()->invoice_id);
    }

    public function test_edit_page_renders_and_update_replaces_the_items(): void
    {
        $client = $this->actingAsUserWithClient();
        $this->post(route('invoices.store'), $this->payload($client));
        $invoice = Invoice::firstOrFail();

        $this->get(route('invoices.edit', $invoice))->assertOk()->assertSee('Save invoice');

        $this->put(route('invoices.update', $invoice), $this->payload($client, [
            'status' => 'sent',
            'items' => [['description' => 'Revised fee', 'quantity' => 1, 'rate' => 7000, 'tax_rate' => 0]],
        ]))->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame(['Revised fee'], $invoice->fresh()->items->pluck('description')->all());
        $this->assertSame('7000.00', $invoice->fresh()->total_amount);
    }

    public function test_invoice_page_shows_items_and_igst_for_another_state(): void
    {
        $client = $this->actingAsUserWithClient();
        FirmProfile::create(['firm_name' => 'Sharma Legal', 'state' => 'Uttar Pradesh', 'is_active' => true]);
        $this->post(route('invoices.store'), $this->payload($client, [
            'place_of_supply' => 'Delhi',
            'items' => [['description' => 'Consultation', 'quantity' => 1, 'rate' => 1000, 'tax_rate' => 18, 'tax_mode' => 'exclusive']],
        ]));

        $this->get(route('invoices.show', Invoice::firstOrFail()))
            ->assertOk()
            ->assertSee('Consultation')
            ->assertSee('IGST')
            ->assertSee('Rs. 180.00')
            ->assertDontSee('CGST');
    }

    public function test_new_invoice_pop_up_asks_for_the_client_then_the_work_and_fee(): void
    {
        $client = $this->actingAsUserWithClient();

        $this->get(route('sheets.invoice'))->assertOk()
            ->assertSee('step 1 of 2')->assertSee('Asha Sharma')
            ->assertSee('data-sheet="'.e(route('sheets.invoice', ['client' => $client->id])).'"', false);

        $this->get(route('sheets.invoice', ['client' => $client->id]))->assertOk()
            ->assertSee('step 2 of 2')
            ->assertSee('name="items[0][description]"', false)
            ->assertSee('Fee already received');

        $this->get(route('billing.index'))->assertOk()
            ->assertSee('data-sheet="'.route('sheets.invoice').'"', false);
    }

    public function test_a_fee_already_received_is_recorded_with_the_invoice(): void
    {
        $client = $this->actingAsUserWithClient();

        $this->post(route('invoices.store'), $this->payload($client, ['received_now' => '1', 'received_by' => 'upi', 'received_reference' => 'UTR123']))
            ->assertRedirect();

        $invoice = Invoice::query()->firstOrFail();
        $this->assertSame('paid', $invoice->status);
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'client_id' => $client->id, 'amount' => 1000, 'payment_method' => 'upi', 'transaction_reference' => 'UTR123']);

        $this->post(route('invoices.store'), $this->payload($client))->assertRedirect();
        $this->assertSame(1, DB::table('payments')->count());
        $this->assertSame('sent', Invoice::query()->latest('id')->firstOrFail()->status);
    }

    private function actingAsUserWithClient(): Client
    {
        $this->actingAs(User::factory()->create());

        return Client::create(['client_type' => 'individual', 'first_name' => 'Asha', 'display_name' => 'Asha Sharma', 'country' => 'India', 'status' => 'active']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 'due_on_receipt',
            'items' => [['description' => 'Legal services', 'quantity' => 1, 'rate' => 1000, 'tax_rate' => 0, 'tax_mode' => 'exclusive']],
        ], $overrides);
    }
}
