<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalCase extends Model
{
    use BelongsToUser;
    use SoftDeletes;

    /**
     * Case statuses offered in forms and filters.
     *
     * @var list<string>
     */
    public const STATUSES = ['pending', 'disposed', 'transferred', 'withdrawn', 'closed', 'other'];

    protected $fillable = [
        'case_title',
        'case_number',
        'file_number',
        'cnr_number',
        'case_type',
        'court_name',
        'court_complex',
        'judge_name',
        'filing_number',
        'filing_date',
        'disposed_on',
        'case_status',
        'stage',
        'petitioner_advocate',
        'respondent_advocate',
        'case_description',
        'source_type',
        'source_reference',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'filing_date' => 'date',
            'disposed_on' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * A short case update for a client, used for WhatsApp and email messages.
     */
    public function clientUpdateMessage(string $clientName, ?string $advocateName = null): string
    {
        $nextHearing = $this->hearings
            ->filter(fn ($hearing) => $hearing->next_hearing_date && Carbon::parse($hearing->next_hearing_date)->startOfDay()->gte(today()))
            ->sortBy('next_hearing_date')
            ->first();

        return collect([
            "Dear {$clientName},",
            'Update on your case: '.($this->case_title ?: 'your matter').($this->case_number ? " ({$this->case_number})" : ''),
            $this->court_name ? 'Court: '.$this->court_name : null,
            'Status: '.ucfirst($this->case_status ?: 'pending').($this->stage ? ', '.$this->stage : ''),
            $nextHearing ? 'Next hearing: '.Carbon::parse($nextHearing->next_hearing_date)->format('d M Y') : null,
            $advocateName ? "Regards,\n{$advocateName}" : null,
        ])->filter()->implode("\n");
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
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

    public function documentFolders(): HasMany
    {
        return $this->hasMany(CaseDocumentFolder::class, 'case_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CaseTag::class, 'case_tag');
    }

    public function causeListAppearances(): HasMany
    {
        return $this->hasMany(
            CauseListAppearance::class,
            'legal_case_id'
        );
    }
}
