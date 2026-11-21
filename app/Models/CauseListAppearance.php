<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CauseListAppearance extends Model
{
    protected $fillable = [
        'court_document_id',
        'legal_case_id',
        'record_key',
        'item_number',
        'case_reference',
        'case_type',
        'classification',
        'court_room',
        'case_title_raw',
        'petitioner_raw',
        'respondent_raw',
        'advocates_raw',
        'judge_name',
        'ia_details',
        'connected_matters',
        'listing_notes',
        'raw_text',
        'source_line',
        'match_status',
        'matched_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'advocates_raw' => 'array',
            'ia_details' => 'array',
            'connected_matters' => 'array',
            'listing_notes' => 'array',
            'matched_at' => 'datetime',
            'source_line' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function courtDocument(): BelongsTo
    {
        return $this->belongsTo(
            CourtDocument::class,
            'court_document_id'
        );
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(
            LegalCase::class,
            'legal_case_id'
        );
    }
}
