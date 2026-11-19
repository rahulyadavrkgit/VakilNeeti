<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'court_id',
        'establishment_id',
        'scope_key',
        'service_key',
        'service_name',
        'service_category',
        'description',
        'official_url',
        'source_url',
        'source_name',
        'link_status',
        'is_active',
        'sort_order',
        'last_verified_at',
        'effective_from',
        'effective_until',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'last_verified_at' => 'datetime',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'metadata' => 'array',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(CourtEstablishment::class, 'establishment_id');
    }
}
