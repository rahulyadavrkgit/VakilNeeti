<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseNote extends Model
{
    use Concerns\BelongsToUser;

    protected $fillable = [
        'case_id',
        'title',
        'body',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }
}
