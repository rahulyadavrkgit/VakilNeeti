<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourtEstablishment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'court_id',
        'establishment_name',
        'establishment_code',
        'establishment_type',
        'country',
        'state',
        'district',
        'city',
        'address',
        'pincode',
        'official_website',
        'official_email',
        'official_phone',
        'description',
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

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
    public function judicialDistricts(): BelongsToMany
{
    return $this->belongsToMany(
        JudicialDistrict::class,
        'court_establishment_judicial_district'
    )->withPivot('is_primary')->withTimestamps();
}
}
