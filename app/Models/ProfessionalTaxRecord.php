<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalTaxRecord extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'firm_profile_id',
        'state',
        'registration_type',
        'registration_number',
        'enrolment_number',
        'period_type',
        'tax_period',
        'due_date',
        'amount',
        'payment_status',
        'payment_date',
        'challan_number',
        'payment_reference',
        'payment_method',
        'return_status',
        'return_filing_date',
        'return_acknowledgement_number',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'return_filing_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function firmProfile(): BelongsTo
    {
        return $this->belongsTo(FirmProfile::class);
    }
}