<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CaseTag extends Model
{
    use Concerns\BelongsToUser;

    protected $fillable = [
        'name',
    ];

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(LegalCase::class, 'case_tag');
    }
}
