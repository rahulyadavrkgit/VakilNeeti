<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToUser;

    /**
     * Ways a fee is paid, most common first.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'upi' => 'UPI',
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'card' => 'Card',
        'other' => 'Other',
    ];

    protected $fillable = [
        'invoice_id',
        'client_id',
        'receipt_number',
        'payment_date',
        'amount',
        'payment_method',
        'transaction_reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
