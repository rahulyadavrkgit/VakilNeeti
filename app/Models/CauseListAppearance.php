<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CauseListAppearance extends Model
{
    /**
     * A supplementary list's title in any High Court: "Supplementary Cause List - 2", "Supplementary List No. 3",
     * "SUPPLEMENTARY LIST". The number, when printed, is the first match.
     */
    public const SUPPLEMENTARY = '/supplementary(?:\s+cause)?\s+list(?:\s*(?:no\.?)?\s*[-:]?\s*(\d+))?/i';

    /**
     * Lists from a High Court: every list source except the Supreme Court and the district courts.
     */
    private static function isHighCourtList(?CourtDocument $document): bool
    {
        return $document !== null && ! in_array($document->provider_key, ['supreme-court', 'district-court'], true);
    }

    protected $fillable = [
        'court_document_id',
        'legal_case_id',
        'record_key',
        'item_number',
        'case_reference',
        'case_type',
        'classification',
        'court_room',
        'case_title_raw',
        'petitioner_raw',
        'respondent_raw',
        'advocates_raw',
        'judge_name',
        'ia_details',
        'connected_matters',
        'listing_notes',
        'raw_text',
        'source_line',
        'match_status',
        'matched_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'advocates_raw' => 'array',
            'ia_details' => 'array',
            'connected_matters' => 'array',
            'listing_notes' => 'array',
            'matched_at' => 'datetime',
            'source_line' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Where the given cases are listed from today on (court, item, bench), earliest first; a case printed
     * in two lists for the same court and item shows once.
     *
     * @param  iterable<int>|Builder<LegalCase>  $caseIds  ids, or a query selecting the ids
     * @return Collection<int, self>
     */
    public static function upcomingForCases(iterable|Builder $caseIds, ?Carbon $until = null): Collection
    {
        return static::query()
            ->with(['courtDocument:id,provider_key,listing_date,document_label,source_url,fetched_at', 'legalCase:id,case_title,case_number,court_name,court_level,state_code,district'])
            ->whereIn('legal_case_id', $caseIds instanceof Builder ? $caseIds : collect($caseIds)->all())
            ->whereHas('courtDocument', fn ($documents) => $documents
                ->whereDate('listing_date', '>=', today())
                ->when($until, fn ($dated) => $dated->whereDate('listing_date', '<=', $until)))
            ->get()
            ->sortBy(fn (self $appearance): array => [$appearance->courtDocument->listing_date->toDateString(), -$appearance->listPriority(), -$appearance->id])
            ->unique(fn (self $appearance): string => $appearance->legal_case_id.'|'.$appearance->courtDocument->listing_date->toDateString().'|'.$appearance->courtDocument->provider_key.'|'.strtolower(trim((string) $appearance->court_room)).'|'.(string) $appearance->item_number)
            ->values();
    }

    /**
     * The court whose list this is, as advocates say it: "Delhi HC", "Allahabad HC", "Supreme Court".
     */
    public function listPriority(): int
    {
        $label = strtolower((string) $this->courtDocument?->document_label);

        return match (true) {
            str_contains($label, 'supplementary') => 2,
            str_starts_with($label, 'advance') => 0,
            default => 1,
        };
    }

    public function courtShortName(): string
    {
        return match ($this->courtDocument?->provider_key) {
            'allahabad-high-court' => 'Allahabad HC',
            'supreme-court' => 'Supreme Court',
            'delhi-high-court' => 'Delhi HC',
            'district-court' => 'District Court',
            default => $this->courtDocument?->court_scope ?: 'Court',
        };
    }

    /**
     * What a hearing strip shows for this listing (components/hearing-strip).
     *
     * @return array{court_short: string, court: ?string, item: ?string, provider: ?string, total: ?int, facts: list<string>, list: ?string, judges: ?string, vc_link: ?string, source_url: ?string}
     */
    public function stripData(): array
    {
        return [
            'court_short' => $this->courtShortName(),
            'provider' => $this->courtDocument?->provider_key,
            'court' => $this->court_room,
            'item' => $this->item_number,
            'total' => $this->metadata['board_total'] ?? null,
            'facts' => $this->boardFacts(),
            'list' => $this->courtDocument?->document_label,
            'judges' => $this->judge_name ? Str::title(mb_strtolower($this->judge_name)) : null,
            'vc_link' => $this->vcLink(),
            'source_url' => $this->courtDocument?->source_url,
            'place' => $this->place(),
        ];
    }

    /**
     * The item in one short line, as advocates write it: "ITEM 97 @ 114", and for a Delhi High Court
     * supplementary list "Supple-2 56-70(15)" (its number, item range and count).
     *
     * @return array{item: ?string, total: ?int, supplementary: ?string}
     */
    public function place(): array
    {
        $meta = $this->metadata ?? [];
        $document = $this->courtDocument;
        $boardTotal = (int) ($meta['board_total'] ?? 0);
        $supplementary = null;
        $total = $boardTotal ?: null;

        if (self::isHighCourtList($document) && preg_match(self::SUPPLEMENTARY, (string) ($document->document_label ?? ''), $number)) {
            $range = ($meta['board_from'] ?? null) && ($meta['board_to'] ?? null) ? $meta['board_from'].'-'.$meta['board_to'] : null;
            $supplementary = trim('Supple'.(isset($number[1]) ? '-'.$number[1] : '-').' '.($range ?? '').($boardTotal ? '('.$boardTotal.')' : ''));
            $total = max($boardTotal, self::courtTotalOn($document, (string) $this->court_room)) ?: null;
        }

        return ['item' => $this->item_number, 'total' => $total, 'supplementary' => $supplementary];
    }

    public function vcLink(): ?string
    {
        return $this->metadata['vc_link'] ?? null;
    }

    /**
     * Where the case stands on its court's board, worded per court (the advocate's rules):
     * - Delhi High Court: the supplementary list it is in, that list's item range and how many supplementary
     *   lists came out that day, and how many cases the board has;
     * - Supreme Court: its place in the order the court hears the board, out of the board's total;
     * - other courts: the item out of the board's total.
     *
     * @return list<string>
     */
    public function boardFacts(): array
    {
        $meta = $this->metadata ?? [];
        $total = (int) ($meta['board_total'] ?? 0);
        $document = $this->courtDocument;
        $label = (string) ($document?->document_label ?? $meta['list'] ?? '');

        if (! $total) {
            return [];
        }

        if ($document?->provider_key === 'district-court') {
            return [$total.' '.($total === 1 ? 'case' : 'cases').' in this list'];
        }

        if ($document?->provider_key === 'supreme-court') {
            $totals = self::courtTotalsOn((string) $document->provider_key, (string) $document->listing_date?->toDateString());
            $dayTotal = max($total, $totals[$this->court_room] ?? 0);
            $acrossCourts = $dayTotal + array_sum(array_diff_key($totals, [(string) $this->court_room => true]));

            return array_values(array_filter([
                isset($meta['board_position']) ? 'No. '.$meta['board_position'].' of '.($meta['board_items'] ?? $total).' in published hearing order' : null,
                $dayTotal.' '.($dayTotal === 1 ? 'case' : 'cases').' before this court that day',
                $acrossCourts > $dayTotal ? $acrossCourts.' cases across imported lists that day' : null,
            ]));
        }

        if (self::isHighCourtList($document) && preg_match(self::SUPPLEMENTARY, $label, $number)) {
            $published = self::supplementaryListsOn($document);

            return array_values(array_filter([
                'Supplementary list'.(isset($number[1]) ? ' '.$number[1] : ''),
                $published ? $published.' supplementary '.($published === 1 ? 'list' : 'lists').' published that day' : null,
                ($meta['board_from'] ?? null) && ($meta['board_to'] ?? null) ? 'items '.$meta['board_from'].'–'.$meta['board_to'] : null,
                $total.' items in this supplementary list',
                max($total, self::courtTotalOn($document, (string) $this->court_room)).' items on this court\'s board',
            ]));
        }

        return [$total.' '.($total === 1 ? 'case' : 'cases').' on this board'];
    }

    /**
     * All cases before one court on the list's date, across every list published for it (not the advance list).
     * Worked out once per request for each court and date.
     */
    private static function courtTotalOn(CourtDocument $document, string $court): int
    {
        return self::courtTotalsOn((string) $document->provider_key, (string) $document->listing_date?->toDateString())[$court] ?? 0;
    }

    /**
     * @return array<string, int>
     */
    private static function courtTotalsOn(string $provider, string $day): array
    {
        return once(fn (): array => CourtDocument::query()
            ->where('provider_key', $provider)
            ->whereDate('listing_date', $day)
            ->where('document_label', 'not like', 'Advance%')
            ->get(['metadata'])
            ->flatMap(fn (CourtDocument $list): array => collect($list->metadata['boards'] ?? [])
                ->map(fn (array $facts, string|int $board): array => ['board' => (string) $board, 'facts' => $facts])->values()->all())
            ->groupBy('board')
            ->map(fn ($lists): int => max(
                (int) $lists->max('facts.total'),
                $lists->flatMap(fn (array $list): array => $list['facts']['items'] ?? [])->unique()->count(),
            ))->all());
    }

    /**
     * How many Delhi HC supplementary lists were published for the list's date (read so far).
     */
    private static function supplementaryListsOn(CourtDocument $document): int
    {
        return self::supplementaryCountOn((string) $document->provider_key, (string) $document->listing_date?->toDateString());
    }

    private static function supplementaryCountOn(string $provider, string $day): int
    {
        return once(fn (): int => CourtDocument::query()
            ->where('provider_key', $provider)
            ->whereDate('listing_date', $day)
            ->where('document_label', 'like', '%Supplementary%')
            ->pluck('document_label')
            ->map(fn (string $label): string => preg_match(self::SUPPLEMENTARY, $label, $matches) && isset($matches[1])
                ? 'list:'.$matches[1] : Str::squish(Str::lower($label)))
            ->unique()->count());
    }

    public function courtDocument(): BelongsTo
    {
        return $this->belongsTo(
            CourtDocument::class,
            'court_document_id'
        );
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(
            LegalCase::class,
            'legal_case_id'
        );
    }
}
