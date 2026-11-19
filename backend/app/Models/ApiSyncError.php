<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSyncError extends Model
{
    protected $fillable = [
        'api_sync_run_id',
        'external_id',
        'error_code',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(ApiSyncRun::class);
    }
}
