<?php

namespace App\Services;

/**
 * Works out invoice line and total amounts with GST.
 *
 * - Exclusive tax: the rate is added on top of quantity × rate.
 * - Inclusive tax: quantity × rate already contains the tax, which is backed out.
 * - A discount lowers the taxable value, so the tax is reduced in the same proportion.
 * - Tax is split into CGST + SGST when the place of supply is the firm's own State, IGST otherwise.
 */
class InvoiceCalculator
{
    /**
     * @param  list<array{quantity: float|int|string, rate: float|int|string, tax_rate?: float|int|string|null, tax_mode?: string|null}>  $lines
     * @return array{lines: list<array{taxable_amount: float, tax_amount: float, line_total: float}>, subtotal: float, discount: float, tax_amount: float, total_amount: float, cgst: float, sgst: float, igst: float}
     */
    public function calculate(array $lines, float|int|string|null $discount = 0, ?string $firmState = null, ?string $placeOfSupply = null): array
    {
        $calculatedLines = [];
        $subtotal = 0.0;
        $lineTax = 0.0;

        foreach ($lines as $line) {
            $gross = (float) $line['quantity'] * (float) $line['rate'];
            $taxRate = max(0.0, (float) ($line['tax_rate'] ?? 0));

            if (($line['tax_mode'] ?? 'exclusive') === 'inclusive') {
                $taxable = $gross / (1 + $taxRate / 100);
                $tax = $gross - $taxable;
            } else {
                $taxable = $gross;
                $tax = $gross * $taxRate / 100;
            }

            $calculatedLines[] = [
                'taxable_amount' => round($taxable, 2),
                'tax_amount' => round($tax, 2),
                'line_total' => round($taxable + $tax, 2),
            ];

            $subtotal += $taxable;
            $lineTax += $tax;
        }

        $discount = min(max(0.0, (float) $discount), $subtotal);
        $taxShare = $subtotal > 0 ? ($subtotal - $discount) / $subtotal : 0.0;
        $tax = round($lineTax * $taxShare, 2);
        $subtotal = round($subtotal, 2);
        $discount = round($discount, 2);

        return [
            'lines' => $calculatedLines,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => round($subtotal - $discount + $tax, 2),
        ] + self::gstSplit($tax, $firmState, $placeOfSupply);
    }

    /**
     * CGST + SGST for supply within the firm's State, IGST for another State. All zero when either
     * State is unknown (the tax is then shown as plain "Tax").
     *
     * @return array{cgst: float, sgst: float, igst: float}
     */
    public static function gstSplit(float $tax, ?string $firmState, ?string $placeOfSupply): array
    {
        $firmState = mb_strtolower(trim((string) $firmState));
        $placeOfSupply = mb_strtolower(trim((string) $placeOfSupply));

        if ($firmState === '' || $placeOfSupply === '') {
            return ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0];
        }

        if ($firmState !== $placeOfSupply) {
            return ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => round($tax, 2)];
        }

        $half = round($tax / 2, 2);

        return ['cgst' => $half, 'sgst' => round($tax - $half, 2), 'igst' => 0.0];
    }
}
