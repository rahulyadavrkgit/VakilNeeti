<?php

namespace Tests\Unit\Enums;

use App\Enums\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    /**
     * @return array<string, array{Currency, float|int|string|null, string}>
     */
    public static function formattedAmounts(): array
    {
        return [
            'rupee uses Rs. with a space' => [Currency::Inr, 1500, 'Rs. 1,500.00'],
            'dirham uses AED with a space' => [Currency::Aed, 99.5, 'AED 99.50'],
            'dollar symbol has no space' => [Currency::Usd, 1234.567, '$1,234.57'],
            'euro symbol has no space' => [Currency::Eur, '20', '€20.00'],
            'missing amount shows zero' => [Currency::Gbp, null, '£0.00'],
        ];
    }

    #[DataProvider('formattedAmounts')]
    public function test_formats_amounts_with_the_currency_symbol(Currency $currency, float|int|string|null $amount, string $expected): void
    {
        $this->assertSame($expected, $currency->format($amount));
    }
}
