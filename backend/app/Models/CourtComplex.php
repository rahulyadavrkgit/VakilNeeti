<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtComplex extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function highCourt(): BelongsTo
    {
        return $this->belongsTo(HighCourt::class);
    }

    public function policeStations(): HasMany
    {
        return $this->hasMany(PoliceStation::class);
    }

    public function barAssociations(): HasMany
    {
        return $this->hasMany(BarAssociation::class);
    }
}