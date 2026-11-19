<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionRelationship extends Model
{
    protected $fillable = [
        'parent_court_id',
        'related_court_id',
        'relationship_type',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function parentCourt(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'parent_court_id');
    }

    public function relatedCourt(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'related_court_id');
    }
}
