<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Court extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'court_name',
        'court_code',
        'court_type',
        'court_level',
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
        'state_id',
        'court_type_id',
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

    public function courtType(): BelongsTo
    {
        return $this->belongsTo(CourtType::class);
    }

    public function documents(): HasMany
{
    return $this->hasMany(CourtDocument::class);
}
    public function judicialDistricts(): HasMany
    {
        return $this->hasMany(JudicialDistrict::class);
    }
public function institutionCategories(): BelongsToMany
{
    return $this->belongsToMany(
        InstitutionCategory::class,
        'court_institution_category',
        'court_id',
        'institution_category_id'
    )
    ->withPivot('is_primary')
    ->withTimestamps();
}

public function courtServices()
{
    return $this->hasMany(CourtService::class);
}
}
