<?php

namespace App\Enums;

/**
 * Currency a user chooses for displaying amounts. Changing it changes the symbol only;
 * stored amounts are not converted.
 */
enum Currency: string
{
    case Inr = 'INR';
    case Usd = 'USD';
    case Eur = 'EUR';
    case Gbp = 'GBP';
    case Aed = 'AED';

    public function symbol(): string
    {
        return match ($this) {
            self::Inr => 'Rs.',
            self::Usd => '$',
            self::Eur => '€',
            self::Gbp => '£',
            self::Aed => 'AED',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Inr => 'Indian Rupee (Rs.)',
            self::Usd => 'US Dollar ($)',
            self::Eur => 'Euro (€)',
            self::Gbp => 'British Pound (£)',
            self::Aed => 'UAE Dirham (AED)',
        };
    }

    public function format(float|int|string|null $amount): string
    {
        $formatted = number_format((float) $amount, 2);

        return in_array($this, [self::Inr, self::Aed], true)
            ? $this->symbol().' '.$formatted
            : $this->symbol().$formatted;
    }

    /**
     * The signed-in user's currency, or Indian Rupee.
     */
    public static function current(): self
    {
        return self::tryFrom((string) auth()->user()?->currency) ?? self::Inr;
    }
}
