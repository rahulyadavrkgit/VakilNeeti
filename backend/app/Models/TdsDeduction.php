<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TdsDeduction extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'invoice_id',
        'client_id',
        'deduction_reference',
        'deduction_date',
        'tds_section',
        'deduction_rate',
        'deduction_base_amount',
        'amount',
        'certificate_number',
        'certificate_date',
        'financial_year',
        'certificate_status',
        'credit_status',
        'credit_checked_at',
        'credit_reference',
        'credit_notes',
        'notes',
    ];

    protected $casts = [
        'deduction_date' => 'date',
        'certificate_date' => 'date',
        'credit_checked_at' => 'date',
        'deduction_rate' => 'decimal:3',
        'deduction_base_amount' => 'decimal:2',
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

    public function followUps(): HasMany
    {
        return $this->hasMany(TdsFollowUp::class)->latest('contact_date');
    }}