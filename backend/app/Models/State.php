<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = [
        'name',
        'code',
        'country_code',
        'is_union_territory',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_union_territory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function judicialDistricts(): HasMany
    {
        return $this->hasMany(JudicialDistrict::class);
    }
}
