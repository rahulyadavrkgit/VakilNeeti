<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtHearing extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'case_id',
        'hearing_date',
        'hearing_time',
        'court_room',
        'judge_name',
        'hearing_stage',
        'proceedings',
        'next_action',
        'next_hearing_date',
        'source_type',
        'source_reference',
    ];

    protected function casts(): array
    {
        return [
            'hearing_date' => 'date',
            'next_hearing_date' => 'date',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }
}