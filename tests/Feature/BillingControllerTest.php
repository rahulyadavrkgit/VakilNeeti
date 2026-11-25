<?php

namespace Tests\Feature;

use App\Models\CaseExpense;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-20 10:00:00');
    }

    public function test_overview_totals_follow_the_period_and_split_due_from_overdue(): void
    {
        $this->actingAsUserWithClient();
        $this->invoice(1000, '2026-11-18', '2026-11-30');
        $overdue = $this->invoice(2000, '2026-11-01', '2026-11-10');
        $this->invoice(5000, '2026-06-01', '2026-06-15');
        $this->payment($overdue, 500, '2026-11-15');

        $response = $this->get(route('billing.index', ['period' => '30']))->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(2, $stats['invoices_raised']);
        $this->assertSame(3000.0, $stats['invoice_amount']);
        $this->assertSame(500.0, $stats['payment_received']);
        $this->assertSame(1000.0, $stats['payment_due']);
        $this->assertSame(6500.0, $stats['payment_overdue']);
    }

    public function test_overdues_separate_pending_and_partly_paid_invoices(): void
    {
        $this->actingAsUserWithClient();
        $pending = $this->invoice(1000, '2026-10-01', '2026-10-15');
        $partial = $this->invoice(2000, '2026-10-01', '2026-10-15');
        $this->payment($partial, 500, '2026-10-20');
        $this->invoice(3000, '2026-11-19', '2026-12-19');

        $response = $this->get(route('billing.overdues'))->assertOk();

        $this->assertSame([$pending->id], $response->viewData('pending')->pluck('id')->all());
        $this->assertSame([$partial->id], $response->viewData('partial')->pluck('id')->all());
        $response->assertSee('36');

        $filtered = $this->get(route('billing.overdues', ['q' => strtolower($partial->invoice_number)]))->assertOk();
        $this->assertSame([$partial->id], $filtered->viewData('partial')->pluck('id')->all());
        $this->assertSame(0, $filtered->viewData('pending')->total());
    }

    public function test_write_off_removes_the_balance_and_can_be_undone(): void
    {
        $this->actingAsUserWithClient();
        $invoice = $this->invoice(1000, '2026-10-01', '2026-10-15');

        $this->post(route('invoices.write-off', $invoice), ['write_off_reason' => 'Client unreachable']);

        $invoice->refresh();
        $this->assertTrue($invoice->isWrittenOff());
        $this->assertSame('written_off', $invoice->status);
        $this->assertSame(0.0, $invoice->balanceDue());
        $this->assertSame(0.0, $this->get(route('billing.index'))->viewData('stats')['payment_overdue']);

        $this->post(route('invoices.undo-write-off', $invoice));
        $this->assertFalse($invoice->fresh()->isWrittenOff());
        $this->assertSame('sent', $invoice->fresh()->status);
    }

    public function test_adds_a_time_entry_only_to_your_own_case(): void
    {
        $this->actingAsUserWithClient();
        $case = $this->makeCase();

        $this->post(route('billing.time.store'), ['case_id' => $case->id, 'description' => 'Drafting', 'entry_date' => '2026-11-20', 'hours' => 1, 'minutes' => 15])
            ->assertRedirect(route('billing.time'));
        $this->assertDatabaseHas('case_time_entries', ['case_id' => $case->id, 'hours' => 1, 'minutes' => 15]);

        $this->actingAsUserWithClient();
        $this->post(route('billing.time.store'), ['case_id' => $case->id, 'description' => 'Sneaky', 'entry_date' => '2026-11-20', 'hours' => 1, 'minutes' => 0])
            ->assertSessionHasErrors(['case_id' => 'Choose one of your cases.']);
    }

    public function test_rejects_a_time_entry_with_no_time(): void
    {
        $this->actingAsUserWithClient();

        $this->post(route('billing.time.store'), ['case_id' => $this->makeCase()->id, 'description' => 'Nothing', 'entry_date' => '2026-11-20', 'hours' => 0, 'minutes' => 0])
            ->assertSessionHasErrors(['minutes' => 'Enter the time spent.']);
    }

    public function test_adds_an_expense_with_a_private_receipt_that_only_the_owner_can_download(): void
    {
        Storage::fake('local');
        $this->actingAsUserWithClient();
        $case = $this->makeCase();

        $this->post(route('billing.expenses.store'), [
            'case_id' => $case->id,
            'description' => 'Court fee stamp',
            'expense_date' => '2026-11-20',
            'amount' => 250,
            'payment_method' => 'upi',
            'receipt' => UploadedFile::fake()->create('stamp.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('billing.expenses'));

        $expense = CaseExpense::firstOrFail();
        $this->assertSame('upi', $expense->payment_method);
        Storage::disk('local')->assertExists($expense->receipt_path);
        $this->get(route('billing.expenses.receipt', $expense))->assertOk()->assertDownload('stamp.pdf');

        $this->actingAsUserWithClient();
        $this->get(route('billing.expenses.receipt', $expense))->assertNotFound();
    }

    public function test_exports_filtered_expenses_as_csv(): void
    {
        $this->actingAsUserWithClient();
        $first = $this->makeCase('First matter');
        $second = $this->makeCase('Second matter');
        CaseExpense::create(['case_id' => $first->id, 'description' => 'Typing', 'amount' => 100, 'expense_date' => '2026-11-10']);
        CaseExpense::create(['case_id' => $second->id, 'description' => 'Travel', 'amount' => 900, 'expense_date' => '2026-11-11']);

        $csv = $this->get(route('billing.expenses.export', ['case_id' => $first->id]))->assertOk()->streamedContent();

        $this->assertStringContainsString('Typing', $csv);
        $this->assertStringNotContainsString('Travel', $csv);
    }

    public function test_exports_payments_with_the_invoice_balance(): void
    {
        $this->actingAsUserWithClient();
        $invoice = $this->invoice(1000, '2026-11-01', '2026-11-15');
        $this->payment($invoice, 400, '2026-11-05');

        $csv = $this->get(route('billing.payments.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString($invoice->invoice_number, $csv);
        $this->assertStringContainsString('600.00', $csv);
    }

    private function actingAsUserWithClient(): void
    {
        $this->actingAs(User::factory()->create());
        $this->client = Client::create(['client_type' => 'individual', 'first_name' => 'Asha', 'display_name' => 'Asha Sharma', 'country' => 'India', 'status' => 'active']);
    }

    private function makeCase(string $title = 'Asha vs State'): LegalCase
    {
        return LegalCase::create(['case_title' => $title, 'court_name' => 'Court', 'case_status' => 'pending']);
    }

    private function invoice(float $total, string $date, string $due): Invoice
    {
        static $number = 0;

        return Invoice::create([
            'client_id' => $this->client->id,
            'invoice_number' => 'T-'.(++$number),
            'invoice_date' => $date,
            'due_date' => $due,
            'currency' => 'INR',
            'subtotal' => $total,
            'discount' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
            'status' => 'sent',
        ]);
    }

    private function payment(Invoice $invoice, float $amount, string $date): Payment
    {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'receipt_number' => 'R-'.$invoice->id.'-'.$amount,
            'payment_date' => $date,
            'amount' => $amount,
            'payment_method' => 'upi',
        ]);
    }
}
