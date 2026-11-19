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

            if ($user && !$user->is_admin) {
                $query->where(
                    $query->getModel()->qualifyColumn('user_id'),
                    $user->id
                );
            }
        });

        static::creating(function ($model) {
            if (!$model->user_id && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }
}