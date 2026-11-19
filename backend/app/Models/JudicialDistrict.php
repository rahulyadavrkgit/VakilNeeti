<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JudicialDistrict extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'state_id',
        'court_id',
        'district_name',
        'district_code',
        'is_active',
        'source_url',
        'source_name',
        'source_status',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_verified_at' => 'datetime',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function establishments(): BelongsToMany
    {
        return $this->belongsToMany(
            CourtEstablishment::class,
            'court_establishment_judicial_district'
        )->withPivot('is_primary')->withTimestamps();
    }
}
