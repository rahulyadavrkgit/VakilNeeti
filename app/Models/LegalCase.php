<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Services\CourtBenchMatcher;
use App\Services\CourtIdentity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    protected static function booted(): void
    {
        // Which court and state the case belongs to: exact from the CNR, otherwise from the advocate's choice or the court's name.
        static::saving(function (LegalCase $case): void {
            $courtChanged = $case->isDirty(['cnr_number', 'court_name', 'court_complex']);
            $choiceChanged = $case->isDirty(['court_level', 'state_code', 'district']);

            if ($case->exists && ! $courtChanged && ! $choiceChanged && $case->court_level) {
                return;
            }

            // A changed court name is read again, unless the advocate picked the court level, state or district just now.
            if ($courtChanged && ! $choiceChanged) {
                $case->forceFill(['court_level' => null, 'state_code' => null, 'district' => null, 'court_code' => null]);
            } elseif ($choiceChanged) {
                $case->court_code = null;
            }

            app(CourtIdentity::class)->fill($case);
        });

        // Every case starts as its own matter; linking an appeal to the lower-court case joins the two (CaseLink).
        static::creating(function (LegalCase $case): void {
            $case->matter_id ??= self::nextMatterId((int) $case->user_id);
        });
    }

    /**
     * The next free matter number in this workspace: "VN-0001", "VN-0002"…
     */
    public static function nextMatterId(int $userId): string
    {
        $sequence = DB::transaction(function () use ($userId): int {
            $workspace = DB::table('users')->where('id', $userId);
            $workspace->increment('matter_sequence');
            $sequence = (int) $workspace->value('matter_sequence');

            if ($sequence !== 1) {
                return $sequence;
            }

            $highest = static::query()->withoutGlobalScopes()->withTrashed()
                ->where('user_id', $userId)
                ->whereNotNull('matter_id')
                ->pluck('matter_id')
                ->map(fn (string $id): int => (int) preg_replace('/\D+/', '', $id))
                ->max() ?? 0;

            $sequence = $highest + 1;
            $workspace->update(['matter_sequence' => $sequence]);

            return $sequence;
        }, 3);

        return 'VN-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * "Supreme Court", "Allahabad HC", "Jalaun, UP"…: which court this case is in, at a glance.
     */
    public function courtLabel(): ?string
    {
        return CourtIdentity::label($this->court_level, $this->state_code, $this->district);
    }

    protected $fillable = [
        'case_title',
        'case_number',
        'file_number',
        'cnr_number',
        'case_type',
        'court_name',
        'court_complex',
        'court_level',
        'state_code',
        'district',
        'diary_number',
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
     * "Supreme Court", "High Court", "District Court" or "Tribunal / Commission".
     */
    public function courtLevel(): string
    {
        if ($this->court_level && isset(CourtIdentity::LEVELS[$this->court_level])) {
            return CourtIdentity::LEVELS[$this->court_level];
        }

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

    /**
     * The two sides named in the case title, "Prahlad Yadav vs Sanjay Kumar & Ors" → [Prahlad Yadav, Sanjay Kumar].
     * The first is the side that brought the matter (petitioner, appellant…).
     *
     * @return array{moving: ?string, answering: ?string}
     */
    public function titleSides(): array
    {
        $parts = preg_split('/\s+(?:vs\.?|v\/s\.?|v\.|versus)\s+/i', trim((string) $this->case_title), 2);
        $clean = fn (?string $name): ?string => ($name = trim((string) preg_replace('/\s*(?:&|and)\s*(?:ors?|others)\.?\s*$/i', '', (string) $name), ' .,-')) !== '' ? $name : null;

        return count($parts) === 2
            ? ['moving' => $clean($parts[0]), 'answering' => $clean($parts[1])]
            : ['moving' => null, 'answering' => null];
    }

    /**
     * The case already in this diary with the same CNR, or the same case number in the same court
     * (the rule imports and the admin duplicate check use). Spaces and letter case do not matter.
     */
    public static function alreadyInDiary(?string $cnr, ?string $caseNumber, ?string $courtName): ?self
    {
        $cnr = strtoupper((string) preg_replace('/\s+/', '', (string) $cnr));
        $squash = fn (?string $value): string => mb_strtolower((string) preg_replace('/\s+/', '', (string) $value));
        $number = $squash($caseNumber);
        $court = $squash($courtName);

        if ($cnr === '' && ($number === '' || $court === '')) {
            return null;
        }

        return self::query()
            ->where(fn ($cases) => $cases
                ->when($cnr !== '', fn ($query) => $query->orWhereRaw('upper(replace(cnr_number, \' \', \'\')) = ?', [$cnr]))
                ->when($number !== '' && $court !== '', fn ($query) => $query->orWhere(fn ($same) => $same
                    ->whereRaw('lower(replace(case_number, \' \', \'\')) = ?', [$number])
                    ->whereRaw('lower(replace(court_name, \' \', \'\')) = ?', [$court]))))
            ->oldest('id')
            ->first();
    }

    /**
     * What a case search can look in. "all" covers every field below.
     *
     * @var array<string, string>
     */
    public const SEARCH_FIELDS = [
        'all' => 'Everything',
        'case_number' => 'Case number',
        'cnr' => 'CNR',
        'party_name' => 'Party name',
        'case_title' => 'Case title',
        'court' => 'Court',
        'judge' => 'Judge',
        'room' => 'Court room',
    ];

    /**
     * Cases matching a search: title, numbers, parties, court and establishment, judge and room (on the case,
     * on its hearings, or found for it in the official court lists), case type, stage and advocates.
     */
    public function scopeSearch(Builder $query, string $term, string $field = 'all'): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '\\%_').'%';
        $field = array_key_exists($field, self::SEARCH_FIELDS) ? $field : 'all';
        $wants = fn (string ...$fields): bool => $field === 'all' || in_array($field, $fields, true);

        // Cases whose judge or room comes only from the official court lists.
        $fromCourtLists = $wants('judge', 'room')
            ? app(CourtBenchMatcher::class)->caseIdsMatching($term, match ($field) {
                'judge' => ['judge'],
                'room' => ['room'],
                default => ['judge', 'room'],
            })
            : [];

        return $query->where(function ($q) use ($like, $wants, $fromCourtLists) {
            $q->whereRaw('1 = 0');

            if ($wants('case_title')) {
                $q->orWhere('case_title', 'like', $like);
            }
            if ($wants('case_number')) {
                $q->orWhere('case_number', 'like', $like)->orWhere('file_number', 'like', $like)->orWhere('filing_number', 'like', $like);
            }
            if ($wants('cnr')) {
                $q->orWhere('cnr_number', 'like', $like);
            }
            if ($wants('party_name')) {
                $q->orWhereHas('parties', fn ($parties) => $parties->where('party_name', 'like', $like))
                    ->orWhere('petitioner_advocate', 'like', $like)
                    ->orWhere('respondent_advocate', 'like', $like);
            }
            if ($wants('court')) {
                $q->orWhere('court_name', 'like', $like)->orWhere('court_complex', 'like', $like);
            }
            if ($wants('judge')) {
                $q->orWhere('judge_name', 'like', $like)
                    ->orWhereHas('hearings', fn ($hearings) => $hearings->where('judge_name', 'like', $like));
            }
            if ($wants('room')) {
                $q->orWhere('court_room', 'like', $like)
                    ->orWhereHas('hearings', fn ($hearings) => $hearings->where('court_room', 'like', $like));
            }
            if ($wants('case_title')) {
                $q->orWhere('case_type', 'like', $like)->orWhere('stage', 'like', $like);
            }
            if ($fromCourtLists !== []) {
                $q->orWhereIn('id', $fromCourtLists);
            }
        });
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
