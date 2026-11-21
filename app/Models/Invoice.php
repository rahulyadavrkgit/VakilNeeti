<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'client_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'currency',
        'subtotal',
        'discount',
        'tax_amount',
        'total_amount',
        'status',
        'description',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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