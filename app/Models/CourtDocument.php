<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtDocument extends Model
{
    use MassPrunable;

    /**
     * How long a cause list and the saved cases' entries in it are kept: hearing dates, rooms and items stay with the cases.
     */
    public const KEEP_MONTHS = 6;

    protected $fillable = [
        'provider_key',
        'court_id',
        'document_key',
        'listing_date',
        'court_scope',
        'category',
        'list_type',
        'sub_type',
        'document_label',
        'source_url',
        'pdf_file_path',
        'text_file_path',
        'mime_type',
        'file_size',
        'checksum',
        'download_status',
        'extraction_status',
        'fetched_at',
        'extracted_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'listing_date' => 'date',
            'fetched_at' => 'datetime',
            'extracted_at' => 'datetime',
            'file_size' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(
            CourtDataSource::class,
            'provider_key',
            'provider_key'
        );
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function appearances(): HasMany
    {
        return $this->hasMany(
            CauseListAppearance::class,
            'court_document_id'
        );
    }

    /**
     * Cause lists dated more than KEEP_MONTHS ago (`model:prune`, daily); their entries go with them.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()->where('list_type', 'cause_list')->whereDate('listing_date', '<', today()->subMonths(self::KEEP_MONTHS));
    }
}
