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

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function linkedCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'linked_case_id');
    }
}
