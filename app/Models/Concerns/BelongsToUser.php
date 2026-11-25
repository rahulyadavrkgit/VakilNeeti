<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::addGlobalScope('user_ownership', function (Builder $query) {
            $user = Auth::user();

            // A junior or clerk works in the workspace of the advocate who invited them.
            if ($user) {
                $query->where(
                    $query->getModel()->qualifyColumn('user_id'),
                    method_exists($user, 'workspaceId') ? $user->workspaceId() : $user->id
                );
            }
        });

        static::creating(function ($model) {
            if (! $model->user_id && Auth::check()) {
                $user = Auth::user();
                $model->user_id = method_exists($user, 'workspaceId') ? $user->workspaceId() : $user->id;
            }
        });
    }
}
