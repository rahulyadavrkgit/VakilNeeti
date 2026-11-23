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

    /**
     * The side the advocate appears for, keyed by stored value.
     *
     * @var array<string, string>
     */
    public const SIDES = [
        'petitioner' => 'Petitioner',
        'respondent' => 'Respondent',
        'appellant' => 'Appellant',
        'plaintiff' => 'Plaintiff',
        'defendant' => 'Defendant',
        'complainant' => 'Complainant',
        'accused' => 'Accused',
        'applicant' => 'Applicant',
        'opposite_party' => 'Opposite party',
        'intervenor' => 'Intervenor',
        'other' => 'Other',
    ];

    /**
     * Sides that brought the matter to court; the rest answer it.
     *
     * @var list<string>
     */
    private const MOVING_SIDES = ['petitioner', 'appellant', 'plaintiff', 'complainant', 'applicant'];

    protected $fillable = [
        'case_title',
        'case_number',
        'file_number',
        'cnr_number',
        'case_type',
        'court_name',
        'court_complex',
        'court_room',
        'vc_link',
        'judge_name',
        'filing_number',
        'filing_date',
        'disposed_on',
        'case_status',
        'appearing_for',
        'appearing_for_parties',
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

    /**
     * "Petitioner", "Respondent"… for the side we appear for, or null when not recorded.
     */
    public function appearingForLabel(): ?string
    {
        return self::sideLabel($this->appearing_for, $this->appearing_for_parties);
    }

    /**
     * The label for a stored side value, or null when none is recorded.
     */
    public static function sideLabel(?string $side, ?string $parties = null): ?string
    {
        $label = self::SIDES[$side] ?? null;
        $parties = trim((string) $parties);

        if ($label === null || $parties === '') {
            return $label;
        }

        // "2" → "Respondent No. 2"; "2, 3" or "1-4" → "Respondent Nos. 2, 3"; names → "Respondent (Ram Kumar)".
        if (preg_match('/^[\d\s,&\-–]+(and[\d\s,&\-–]+)*$/i', $parties)) {
            return $label.(preg_match('/^\d+$/', $parties) ? ' No. ' : ' Nos. ').$parties;
        }

        return $label.' ('.$parties.')';
    }

    /**
     * Whether a stored side is one that brought the matter (petitioner, appellant…).
     */
    public static function isMovingSide(?string $side): bool
    {
        return in_array($side, self::MOVING_SIDES, true);
    }

    /**
     * Whether we appear for the side that brought the matter (petitioner, appellant…).
     */
    public function appearsForMovingSide(): bool
    {
        return self::isMovingSide($this->appearing_for);
    }

    /**
     * "Supreme Court", "High Court" or "District Court", read from the court name.
     */
    public function courtLevel(): string
    {
        $court = mb_strtolower((string) $this->court_name);

        return match (true) {
            str_contains($court, 'supreme court') => 'Supreme Court',
            str_contains($court, 'high court') => 'High Court',
            $court === '' => 'Court',
            default => 'District Court',
        };
    }

    /**
     * The same matter across courts: lower-court cases it arose from (oldest first), cases
     * that arose from it in higher courts, and connected matters. Each entry carries the
     * link id so it can be removed.
     *
     * @return array{lower: list<array{link_id: int, case: LegalCase}>, higher: list<array{link_id: int, case: LegalCase}>, connected: list<array{link_id: int, case: LegalCase}>}
     */
    public function journey(): array
    {
        $links = CaseLink::query()
            ->withoutGlobalScopes()
            ->where('user_id', $this->user_id)
            ->get(['id', 'case_id', 'linked_case_id', 'relation']);

        $walk = function (string $from, string $to) use ($links): array {
            $steps = [];
            $seen = [(int) $this->id => true];
            $current = (int) $this->id;

            for ($depth = 0; $depth < 6; $depth++) {
                $link = $links->first(fn (CaseLink $link) => $link->relation === 'arises_from'
                    && (int) $link->{$from} === $current
                    && ! isset($seen[(int) $link->{$to}]));

                if (! $link) {
                    break;
                }

                $current = (int) $link->{$to};
                $seen[$current] = true;
                $steps[] = ['link_id' => (int) $link->id, 'case_id' => $current];
            }

            return $steps;
        };

        $lower = array_reverse($walk('case_id', 'linked_case_id'));
        $higher = $walk('linked_case_id', 'case_id');
        $connected = $links
            ->filter(fn (CaseLink $link) => $link->relation === 'connected'
                && ((int) $link->case_id === (int) $this->id || (int) $link->linked_case_id === (int) $this->id))
            ->map(fn (CaseLink $link) => [
                'link_id' => (int) $link->id,
                'case_id' => (int) $link->case_id === (int) $this->id ? (int) $link->linked_case_id : (int) $link->case_id,
            ])
            ->values()
            ->all();

        $cases = self::query()
            ->withoutGlobalScope('user_ownership')
            ->where('user_id', $this->user_id)
            ->whereIn('id', array_column([...$lower, ...$higher, ...$connected], 'case_id'))
            ->get()
            ->keyBy('id');

        $withCases = fn (array $steps): array => array_values(array_filter(array_map(
            fn (array $step) => $cases->has($step['case_id']) ? ['link_id' => $step['link_id'], 'case' => $cases->get($step['case_id'])] : null,
            $steps,
        )));

        return [
            'lower' => $withCases($lower),
            'higher' => $withCases($higher),
            'connected' => $withCases($connected),
        ];
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
