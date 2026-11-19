<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientActivity extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'client_id',
        'activity_type',
        'subject',
        'description',
        'activity_at',
        'follow_up_at',
        'status',
    ];

    protected $casts = [
        'activity_at' => 'datetime',
        'follow_up_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}