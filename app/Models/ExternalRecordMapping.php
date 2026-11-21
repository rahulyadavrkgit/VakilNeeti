<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalRecordMapping extends Model
{
    protected $fillable = [
        'provider_key',
        'entity_type',
        'local_table',
        'local_record_id',
        'external_id',
        'external_parent_id',
        'source_url',
        'source_updated_at',
        'last_synced_at',
        'payload_hash',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'source_updated_at' => 'datetime',
            'last_synced_at' => 'datetime',
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
}
