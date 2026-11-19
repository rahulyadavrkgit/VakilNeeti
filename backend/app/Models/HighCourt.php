<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HighCourt extends Model
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

    public function courtComplexes(): HasMany
    {
        return $this->hasMany(CourtComplex::class);
    }
}