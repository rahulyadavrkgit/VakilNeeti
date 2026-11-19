<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    use SoftDeletes;

    protected $fillable = [
        'case_title',
        'case_number',
        'cnr_number',
        'case_type',
        'court_name',
        'court_complex',
        'judge_name',
        'filing_number',
        'filing_date',
        'case_status',
        'case_description',
        'source_type',
        'source_reference',
    ];

    protected function casts(): array
    {
        return [
            'filing_date' => 'date',
        ];
    }

    public function parties(): HasMany
    {
        return $this->hasMany(CaseParty::class, 'case_id');
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(CourtHearing::class, 'case_id');
    }
    public function causeListAppearances(): HasMany
{
    return $this->hasMany(
        CauseListAppearance::class,
        'legal_case_id'
    );
}
}