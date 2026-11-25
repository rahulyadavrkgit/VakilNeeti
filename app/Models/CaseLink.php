<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A link between two of an advocate's cases: the case "arises from" the linked
 * lower-court case (appeal, revision, SLP), or the two are connected matters.
 */
class CaseLink extends Model
{
    use Concerns\BelongsToUser;

    /**
     * @var array<string, string>
     */
    public const RELATIONS = [
        'arises_from' => 'Appeal / revision from',
        'connected' => 'Connected matter',
    ];

    protected $fillable = [
        'user_id',
        'case_id',
        'linked_case_id',
        'relation',
    ];

    protected static function booted(): void
    {
        // An appeal, revision or SLP is the same matter as the lower-court case it arises from:
        // it (and anything already joined to it) takes the lower case's matter number.
        static::created(fn (CaseLink $link) => $link->syncMatter());
    }

    public function syncMatter(): void
    {
        if ($this->relation !== 'arises_from') {
            return;
        }

        $cases = LegalCase::query()->withoutGlobalScopes()->where('user_id', $this->user_id)->whereIn('id', [$this->case_id, $this->linked_case_id])->get()->keyBy('id');
        $lower = $cases[$this->linked_case_id] ?? null;
        $higher = $cases[$this->case_id] ?? null;

        if (! $lower?->matter_id || ! $higher || $higher->matter_id === $lower->matter_id) {
            return;
        }

        LegalCase::query()->withoutGlobalScopes()
            ->where('user_id', $higher->user_id)
            ->where(fn ($query) => $query->whereKey($higher->id)->when($higher->matter_id, fn ($same) => $same->orWhere('matter_id', $higher->matter_id)))
            ->update(['matter_id' => $lower->matter_id]);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function linkedCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'linked_case_id');
    }
}
