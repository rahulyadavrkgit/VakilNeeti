<?php

namespace Tests\Unit\Services;

use App\Services\InvoiceCalculator;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    public function test_adds_exclusive_gst_on_top_of_the_line_amount(): void
    {
        $totals = (new InvoiceCalculator)->calculate([
            ['quantity' => 2, 'rate' => 5000, 'tax_rate' => 18, 'tax_mode' => 'exclusive'],
        ]);

        $this->assertSame(10000.0, $totals['subtotal']);
        $this->assertSame(1800.0, $totals['tax_amount']);
        $this->assertSame(11800.0, $totals['total_amount']);
        $this->assertSame(['taxable_amount' => 10000.0, 'tax_amount' => 1800.0, 'line_total' => 11800.0], $totals['lines'][0]);
    }

    public function test_backs_out_inclusive_gst_from_the_line_amount(): void
    {
        $totals = (new InvoiceCalculator)->calculate([
            ['quantity' => 1, 'rate' => 11800, 'tax_rate' => 18, 'tax_mode' => 'inclusive'],
        ]);

        $this->assertSame(10000.0, $totals['subtotal']);
        $this->assertSame(1800.0, $totals['tax_amount']);
        $this->assertSame(11800.0, $totals['total_amount']);
    }

    public function test_discount_lowers_the_taxable_value_and_the_tax(): void
    {
        $totals = (new InvoiceCalculator)->calculate([
            ['quantity' => 1, 'rate' => 10000, 'tax_rate' => 18],
        ], 1000);

        $this->assertSame(1000.0, $totals['discount']);
        $this->assertSame(1620.0, $totals['tax_amount']);
        $this->assertSame(10620.0, $totals['total_amount']);
    }

    public function test_discount_cannot_exceed_the_subtotal(): void
    {
        $totals = (new InvoiceCalculator)->calculate([['quantity' => 1, 'rate' => 500]], 9999);

        $this->assertSame(500.0, $totals['discount']);
        $this->assertSame(0.0, $totals['total_amount']);
    }

    public function test_mixes_taxed_and_untaxed_lines(): void
    {
        $totals = (new InvoiceCalculator)->calculate([
            ['quantity' => 1, 'rate' => 10000, 'tax_rate' => 18],
            ['quantity' => 1, 'rate' => 750, 'tax_rate' => 0],
        ]);

        $this->assertSame(10750.0, $totals['subtotal']);
        $this->assertSame(1800.0, $totals['tax_amount']);
        $this->assertSame(12550.0, $totals['total_amount']);
    }

    public function test_splits_tax_into_cgst_and_sgst_within_the_same_state(): void
    {
        $totals = (new InvoiceCalculator)->calculate([['quantity' => 1, 'rate' => 1000, 'tax_rate' => 18]], 0, 'Uttar Pradesh', 'uttar pradesh');

        $this->assertSame([90.0, 90.0, 0.0], [$totals['cgst'], $totals['sgst'], $totals['igst']]);
    }

    public function test_charges_igst_for_another_state(): void
    {
        $totals = (new InvoiceCalculator)->calculate([['quantity' => 1, 'rate' => 1000, 'tax_rate' => 18]], 0, 'Uttar Pradesh', 'Delhi');

        $this->assertSame([0.0, 0.0, 180.0], [$totals['cgst'], $totals['sgst'], $totals['igst']]);
    }

    public function test_does_not_label_the_tax_when_a_state_is_unknown(): void
    {
        $totals = (new InvoiceCalculator)->calculate([['quantity' => 1, 'rate' => 1000, 'tax_rate' => 18]], 0, null, 'Delhi');

        $this->assertSame([0.0, 0.0, 0.0], [$totals['cgst'], $totals['sgst'], $totals['igst']]);
        $this->assertSame(180.0, $totals['tax_amount']);
    }
}
