<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtRecordImport extends Model
{
    use Concerns\BelongsToUser;

    protected $fillable = [
        'source_name',
        'source_url',
        'raw_content',
        'parsed_data',
        'import_status',
        'legal_case_id',
        'retrieved_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'retrieved_at' => 'datetime',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }
}
