<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncedCourtDataRecord extends Model
{
    protected $table = 'court_data_records';

    protected $fillable = [
        'provider_key',
        'entity_type',
        'external_id',
        'external_parent_id',
        'display_name',
        'attributes',
        'source_url',
        'source_updated_at',
        'fetched_at',
        'payload_hash',
        'record_status',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'source_updated_at' => 'datetime',
            'fetched_at' => 'datetime',
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
