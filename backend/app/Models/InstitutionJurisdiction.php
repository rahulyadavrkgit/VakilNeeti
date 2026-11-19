<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionJurisdiction extends Model
{
    protected $fillable = [
        'court_id',
        'state_id',
        'judicial_district_id',
        'jurisdiction_type',
        'description',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function judicialDistrict(): BelongsTo
    {
        return $this->belongsTo(JudicialDistrict::class);
    }
}
