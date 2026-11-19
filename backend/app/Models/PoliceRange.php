<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoliceRange extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function policeDistricts(): HasMany
    {
        return $this->hasMany(PoliceDistrict::class);
    }
}