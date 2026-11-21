<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtCatalogueChangeLog extends Model
{
    protected $fillable = [
        'provider_key',
        'entity_type',
        'local_table',
        'local_record_id',
        'external_id',
        'field_name',
        'old_value',
        'new_value',
        'source_url',
        'source_name',
        'api_sync_run_id',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'changed_at' => 'datetime',
        ];
    }

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(ApiSyncRun::class, 'api_sync_run_id');
    }
}