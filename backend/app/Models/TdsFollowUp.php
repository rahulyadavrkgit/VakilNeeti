<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdsFollowUp extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'tds_deduction_id',
        'contact_date',
        'contact_method',
        'follow_up_status',
        'outcome',
        'notes',
        'next_follow_up_date',
    ];

    protected $casts = [
        'contact_date' => 'date',
        'next_follow_up_date' => 'date',
    ];

    public function tdsDeduction(): BelongsTo
    {
        return $this->belongsTo(TdsDeduction::class);
    }
}