<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiSyncRun extends Model
{
    protected $fillable = [
        'provider_key',
        'entity_type',
        'sync_mode',
        'status',
        'started_at',
        'finished_at',
        'records_received',
        'records_created',
        'records_updated',
        'records_skipped',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(
            CourtDataSource::class,
            'provider_key',
            'provider_key'
        );
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ApiSyncError::class);
    }
}
