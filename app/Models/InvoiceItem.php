<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'rate',
        'tax_rate',
        'tax_mode',
        'taxable_amount',
        'tax_amount',
        'line_total',
        'source_type',
        'source_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
