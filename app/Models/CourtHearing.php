<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtHearing extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'case_id',
        'hearing_date',
        'hearing_time',
        'court_room',
        'vc_link',
        'judge_name',
        'hearing_stage',
        'proceedings',
        'next_action',
        'next_hearing_date',
        'source_type',
        'source_reference',
    ];

    protected function casts(): array
    {
        return [
            'hearing_date' => 'date',
            'next_hearing_date' => 'date',
        ];
    }

    /**
     * Hearings the user typed in or brought from their own calendar can be edited; imported court records stay as recorded.
     */
    public function isManual(): bool
    {
        return in_array($this->source_type, [null, '', 'manual', 'google_calendar'], true);
    }

    public function dateSourceLabel(): string
    {
        return filled($this->source_type) && $this->source_type !== 'manual' ? 'Imported' : 'Saved by you';
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }
}
