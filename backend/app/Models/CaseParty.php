<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParty extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'case_id',
        'client_id',
        'external_party_id',
        'party_name',
        'party_type',
        'party_number',
        'is_client',
        'relationship_type',
    ];

    protected function casts(): array
    {
        return [
            'is_client' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }
}