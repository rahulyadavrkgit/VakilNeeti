<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToUser;

    /**
     * Payment terms => days after the invoice date the invoice is due (null = pick a date).
     *
     * @var array<string, int|null>
     */
    public const PAYMENT_TERMS = [
        'due_on_receipt' => 0,
        'net_7' => 7,
        'net_15' => 15,
        'net_30' => 30,
        'net_45' => 45,
        'net_60' => 60,
        'custom' => null,
    ];

    /**
     * @var list<string>
     */
    public const STATUSES = ['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled', 'written_off'];

    protected $fillable = [
        'client_id',
        'legal_case_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'payment_terms',
        'currency',
        'subtotal',
        'discount',
        'tax_amount',
        'total_amount',
        'status',
        'description',
        'notes',
        'client_pan',
        'client_gstin',
        'place_of_supply',
        'billing_address',
        'written_off_at',
        'write_off_reason',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'written_off_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public static function paymentTermLabel(string $term): string
    {
        return match ($term) {
            'due_on_receipt' => 'Due on receipt',
            'custom' => 'Custom date',
            default => 'Net '.self::PAYMENT_TERMS[$term].' days',
        };
    }

    public function amountPaid(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function balanceDue(): float
    {
        return $this->isWrittenOff() ? 0.0 : max(0.0, (float) $this->total_amount - $this->amountPaid());
    }

    public function isWrittenOff(): bool
    {
        return $this->written_off_at !== null;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function tdsDeductions(): HasMany
    {
        return $this->hasMany(TdsDeduction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
