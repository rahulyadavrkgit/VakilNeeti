<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $fillable = [
        'state_id',
        'judicial_district_id',
        'name',
        'lgd_code',
        'lgd_name',
        'body_type',
        'pincode',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lgd_code' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(JudicialDistrict::class, 'judicial_district_id');
    }
}
