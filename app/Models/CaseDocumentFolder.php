<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseDocumentFolder extends Model
{
    use Concerns\BelongsToUser;

    protected $fillable = [
        'case_id',
        'parent_id',
        'name',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'folder_id');
    }

    /**
     * This folder and its parents, from the top level down.
     *
     * @return list<self>
     */
    public function trail(): array
    {
        $trail = [];
        $folder = $this;

        while ($folder !== null && count($trail) < 20) {
            array_unshift($trail, $folder);
            $folder = $folder->parent;
        }

        return $trail;
    }
}
