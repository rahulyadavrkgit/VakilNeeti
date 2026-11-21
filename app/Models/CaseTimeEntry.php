<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseTimeEntry extends Model
{
    use Concerns\BelongsToUser;

    protected $fillable = [
        'case_id',
        'description',
        'entry_date',
        'hours',
        'minutes',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }
}
