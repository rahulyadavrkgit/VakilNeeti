<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'requires_state',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_state' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }
}
