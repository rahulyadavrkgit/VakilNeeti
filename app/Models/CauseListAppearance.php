<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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

    /**
     * Where the given cases are listed from today on (court, item, bench), earliest first; a case printed
     * in two lists for the same court and item shows once.
     *
     * @param  iterable<int>|Builder<LegalCase>  $caseIds  ids, or a query selecting the ids
     * @return Collection<int, self>
     */
    public static function upcomingForCases(iterable|Builder $caseIds, ?Carbon $until = null): Collection
    {
        return static::query()
            ->with(['courtDocument:id,listing_date,document_label,source_url,fetched_at', 'legalCase:id,case_title,case_number,court_name,court_level,state_code,district'])
            ->whereIn('legal_case_id', $caseIds instanceof Builder ? $caseIds : collect($caseIds)->all())
            ->whereHas('courtDocument', fn ($documents) => $documents
                ->whereDate('listing_date', '>=', today())
                ->when($until, fn ($dated) => $dated->whereDate('listing_date', '<=', $until)))
            ->get()
            ->sortBy(fn (self $appearance): string => $appearance->courtDocument->listing_date->format('Ymd').str_pad((string) $appearance->court_room, 4, '0', STR_PAD_LEFT).str_pad((string) $appearance->item_number, 5, '0', STR_PAD_LEFT))
            ->unique(fn (self $appearance): string => $appearance->legal_case_id.'|'.$appearance->courtDocument->listing_date->toDateString().'|'.$appearance->court_room.'|'.$appearance->item_number)
            ->values();
    }

    public function vcLink(): ?string
    {
        return $this->metadata['vc_link'] ?? null;
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
