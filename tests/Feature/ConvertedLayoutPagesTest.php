<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * These pages used to be standalone HTML documents; they now render inside the main layout.
 */
class ConvertedLayoutPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_pages_render_inside_the_main_layout_with_one_back_button(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::create(['first_name' => 'Asha']);
        $case = LegalCase::create(['case_title' => 'Asha v. State', 'court_name' => 'District Court Lucknow']);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-'.Str::upper(Str::random(6)),
            'invoice_date' => now()->toDateString(),
            'subtotal' => 1500,
            'total_amount' => 1500,
        ]);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'receipt_number' => 'RCPT-1',
            'payment_date' => now()->toDateString(),
            'amount' => 500,
        ]);

        $pages = [
            route('cases.show', $case),
            route('clients.create'),
            route('clients.edit', $client),
            route('clients.activities.create', $client),
            route('invoices.show', $invoice),
            route('payments.show', $payment),
            route('pdf-annotator.index'),
        ];

        foreach ($pages as $url) {
            $response = $this->get($url)->assertOk();
            $html = $response->getContent();

            $this->assertSame(1, substr_count($html, '<!DOCTYPE html>'), "$url should be a single HTML document");
            $this->assertStringContainsString('id="vn-sidebar"', $html, "$url should use the main layout");
            $this->assertSame(1, preg_match_all('/<a\s[^>]*data-vn-back/', $html), "$url should have exactly one Back button");
        }
    }

    public function test_invoice_amounts_use_the_users_currency(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create(['first_name' => 'Asha']);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-TEST01',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 1500,
            'total_amount' => 1500,
        ]);

        $this->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Rs. 1,500.00');
    }
}
