<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'client_id',
        'case_id',
        'title',
        'description',
        'due_at',
        'priority',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && !in_array($this->status, ['completed', 'cancelled'], true);
    }
}