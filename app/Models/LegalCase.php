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

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class, 'case_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(CaseAppointment::class, 'case_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CaseExpense::class, 'case_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(CaseTimeEntry::class, 'case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id');
    }

    public function causeListAppearances(): HasMany
    {
        return $this->hasMany(
            CauseListAppearance::class,
            'legal_case_id'
        );
    }
}