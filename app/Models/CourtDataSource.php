<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtDataSource extends Model
{
    protected $fillable = [
        'provider_key',
        'provider_name',
        'source_type',
        'base_url',
        'terms_url',
        'attribution_text',
        'is_enabled',
        'last_synced_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(ApiSyncRun::class, 'provider_key', 'provider_key');
    }
}
