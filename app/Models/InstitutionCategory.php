<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InstitutionCategory extends Model
{
    protected $fillable = [
        'category_key',
        'name',
        'parent_category_key',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function courts(): BelongsToMany
    {
        return $this->belongsToMany(
            Court::class,
            'court_institution_category',
            'institution_category_id',
            'court_id'
        )
        ->withPivot('is_primary')
        ->withTimestamps();
    }
}
