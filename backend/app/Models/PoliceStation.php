<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoliceStation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_specialist_unit' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function policeDistrict(): BelongsTo
    {
        return $this->belongsTo(PoliceDistrict::class);
    }

    public function subDivision(): BelongsTo
    {
        return $this->belongsTo(PoliceSubDivision::class, 'police_sub_division_id');
    }

    public function courtComplex(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CourtComplex::class);
    }
}