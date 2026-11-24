<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An invitation for a junior or clerk to work in an advocate's workspace. It waits by email (or mobile number)
 * and is accepted the first time someone with that email or number signs in (see User::acceptTeamInvite).
 */
class TeamInvite extends Model
{
    protected $fillable = ['owner_id', 'email', 'mobile', 'role', 'accepted_user_id', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }
}
