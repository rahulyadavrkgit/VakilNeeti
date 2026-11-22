<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_record_a_payment_against_another_users_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['client_type' => 'individual', 'first_name' => 'Asha', 'country' => 'India', 'status' => 'active']);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV/2026/0001',
            'invoice_date' => '2026-11-01',
            'currency' => 'INR',
            'subtotal' => 1000,
            'discount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => 'sent',
        ]);

        $this->actingAs(User::factory()->create());

        $this->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-11-05',
            'amount' => 500,
            'payment_method' => 'upi',
        ])->assertSessionHasErrors(['invoice_id' => 'Choose one of your invoices.']);

        $this->assertDatabaseCount('payments', 0);
    }
}
