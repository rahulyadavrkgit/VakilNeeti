<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\CarbonInterface;

/**
 * Builds the next invoice number from the user's format, e.g. "{PREFIX}/{YEAR}/{NUMBER}" → "INV/2026/0007".
 * The running number restarts each year and skips numbers already used.
 */
class InvoiceNumberGenerator
{
    public const DEFAULT_PREFIX = 'INV';

    public const DEFAULT_FORMAT = '{PREFIX}/{YEAR}/{NUMBER}';

    public function next(CarbonInterface $invoiceDate, ?string $prefix = null, ?string $format = null): string
    {
        $prefix = trim((string) $prefix) ?: self::DEFAULT_PREFIX;
        $format = trim((string) $format) ?: self::DEFAULT_FORMAT;

        if (! str_contains($format, '{NUMBER}')) {
            $format .= '/{NUMBER}';
        }

        $sequence = Invoice::query()->whereYear('invoice_date', $invoiceDate->year)->count() + 1;

        do {
            $number = $this->format($format, $prefix, $invoiceDate, $sequence++);
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    public function format(string $format, string $prefix, CarbonInterface $invoiceDate, int $sequence): string
    {
        return strtr($format, [
            '{PREFIX}' => $prefix,
            '{YEAR}' => $invoiceDate->format('Y'),
            '{MONTH}' => $invoiceDate->format('m'),
            '{NUMBER}' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
        ]);
    }
}
