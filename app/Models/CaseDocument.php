<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class CaseDocument extends Model
{
    use Concerns\BelongsToUser;

    protected $fillable = [
        'case_id',
        'folder_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size',
    ];

    /**
     * @param  iterable<int>  $caseIds
     * @return Collection<int, self>
     */
    public static function latestOrdersForCases(iterable $caseIds): Collection
    {
        return self::query()->whereIn('case_id', $caseIds)->where('mime_type', 'application/pdf')
            ->where(fn ($documents) => $documents->whereLike('original_name', '%order%')->orWhereLike('original_name', '%judgment%'))
            ->latest('created_at')->latest('id')->get(['id', 'case_id', 'original_name', 'created_at'])
            ->filter(fn (self $document): bool => (bool) preg_match('/(?:^|[^a-z])(?:order|judgment)(?:[^a-z]|$)/i', $document->original_name))
            ->unique('case_id')->keyBy('case_id');
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }
}
