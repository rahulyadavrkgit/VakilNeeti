<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvoiceShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_page_offers_a_signed_link_and_the_clients_whatsapp_number(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = $this->invoice();

        $response = $this->get(route('invoices.show', $invoice))->assertOk()
            ->assertSee('data-whatsapp-number="919876543210"', false)
            ->assertSee('Share PDF on WhatsApp');

        $this->assertStringContainsString('/shared/invoices/'.$invoice->id.'?expires=', $response->viewData('shareUrl'));
    }

    public function test_the_signed_link_opens_the_pdf_for_anyone_who_has_it(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = $this->invoice();
        $url = URL::temporarySignedRoute('invoices.shared-pdf', now()->addDays(30), ['invoice' => $invoice->id]);

        auth()->logout();
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs(User::factory()->create());
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unsigned_tampered_or_expired_links_are_refused(): void
    {
        $this->actingAs(User::factory()->create());
        $invoice = $this->invoice();
        $other = $this->invoice('INV-2');
        auth()->logout();

        $this->get('/shared/invoices/'.$invoice->id)->assertForbidden();

        $signed = URL::temporarySignedRoute('invoices.shared-pdf', now()->addDays(30), ['invoice' => $invoice->id]);
        $this->get(str_replace('/shared/invoices/'.$invoice->id, '/shared/invoices/'.$other->id, $signed))->assertForbidden();

        $expired = URL::temporarySignedRoute('invoices.shared-pdf', now()->subMinute(), ['invoice' => $invoice->id]);
        $this->get($expired)->assertForbidden();
    }

    private function invoice(string $number = 'INV-1'): Invoice
    {
        $client = Client::create(['client_code' => Client::nextCode(), 'client_type' => 'individual', 'first_name' => 'Asha', 'mobile' => '9876543210', 'status' => 'active']);

        return Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => $number,
            'invoice_date' => '2026-11-01',
            'due_date' => '2026-11-30',
            'currency' => 'INR',
            'subtotal' => 1000,
            'discount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => 'sent',
        ]);
    }
}
