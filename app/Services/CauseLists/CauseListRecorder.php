<?php

namespace App\Services\CauseLists;

use App\Models\CauseListAppearance;
use App\Models\CourtDataSource;
use App\Models\CourtDocument;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\DelhiHighCourtCauseList;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * The part every court's cause-list check shares: download a published list (PDF) once, read it,
 * keep where each saved case is listed (court, item, bench, VC link), and put one note in the bell
 * per case and date. Each court only says where its lists are and how to read one.
 */
class CauseListRecorder
{
    /** A cause list larger than this is not downloaded (the biggest daily lists are about 20 MB). */
    private const MAX_PDF_BYTES = 40_000_000;

    /**
     * Read one list and record the saved cases in it. Returns how many listings of saved cases were found.
     *
     * @param  array{title: string, url: string}  $listing
     * @param  array<string, list<array{id: int, user_id: int, title: string}>>  $casesByKey
     * @param  Closure(string): list<array{key: string, reference: string, item: string, court: ?string, judges: list<string>, vc_link: ?string, line: int, board_section?: ?string}>  $entriesFromPdf  reads the PDF's contents
     * @param  Closure(string): void|null  $report  progress lines for the console
     */
    public function check(string $provider, string $courtShort, string $scope, array $listing, Carbon $date, array $casesByKey, Closure $entriesFromPdf, ?Closure $report = null): int
    {
        $document = CourtDocument::query()->firstOrNew(['provider_key' => $provider, 'document_key' => sha1($listing['url'])]);

        $caseKeys = collect($casesByKey)->map(fn (array $cases): array => collect($cases)->pluck('id')->sort()->values()->all())->sortKeys();
        $version = $provider === 'delhi-high-court' ? DelhiHighCourtCauseList::EXTRACTION_VERSION : '';
        $fingerprint = hash('sha256', $version.$caseKeys->toJson());

        if ($document->exists && $document->extraction_status === 'done'
            && ($document->metadata['matched_case_fingerprint'] ?? null) === $fingerprint) {
            return 0;
        }

        $document->fill([
            'listing_date' => $date->toDateString(),
            'court_scope' => $scope,
            'list_type' => 'cause_list',
            'document_label' => mb_substr($listing['title'], 0, 250),
            'source_url' => $listing['url'],
            'fetched_at' => now(),
        ]);

        try {
            $pdf = Http::timeout(120)->connectTimeout(10)->withUserAgent('Mozilla/5.0 (VakilNiti cause list check)')->get($listing['url']);

            if (! $pdf->successful() || strlen($pdf->body()) > self::MAX_PDF_BYTES || ! str_starts_with($pdf->body(), '%PDF')) {
                $document->fill(['download_status' => 'failed', 'extraction_status' => 'pending'])->save();

                return 0;
            }

            $document->fill(['download_status' => 'done', 'mime_type' => 'application/pdf', 'file_size' => strlen($pdf->body()), 'checksum' => hash('sha256', $pdf->body())]);
            $entries = $entriesFromPdf($pdf->body());
        } catch (Throwable $exception) {
            // A warning, not an error: one unreadable list should not raise the morning error alert every day.
            Log::warning("{$courtShort} cause list could not be read", ['url' => $listing['url'], 'message' => $exception->getMessage()]);
            $document->fill(['extraction_status' => 'failed'])->save();

            return 0;
        }

        $matches = collect($entries)->filter(fn (array $entry): bool => isset($casesByKey[$entry['key']]));
        $boards = self::boards($entries, $provider !== 'supreme-court');
        $sectionBoards = collect($entries)
            ->filter(fn (array $entry): bool => isset($entry['board_section']))
            ->groupBy('board_section')
            ->map(fn ($entries): array => self::boards($entries->all()))
            ->all();

        DB::transaction(function () use ($document, $matches, $casesByKey, $entries, $boards, $sectionBoards, $fingerprint) {
            $document->fill(['extraction_status' => 'done', 'extracted_at' => now(), 'metadata' => [
                'entries' => count($entries),
                'matched_case_fingerprint' => $fingerprint,
                'boards' => collect($boards)->map(fn (array $items): array => ['total' => count($items), 'from' => $items[0] ?? null, 'to' => end($items) ?: null, 'items' => $items])->all(),
                'supplementary_items' => $sectionBoards['supplementary'] ?? [],
            ]])->save();
            $document->appearances()->delete();

            foreach ($matches as $entry) {
                $place = self::placeOnBoard($sectionBoards[$entry['board_section'] ?? ''] ?? $boards, $entry);

                foreach ($casesByKey[$entry['key']] as $case) {
                    CauseListAppearance::query()->updateOrCreate(
                        ['court_document_id' => $document->id, 'record_key' => sha1($case['id'].'|'.$entry['key'].'|'.$entry['item'].'|'.$entry['court'].'|'.($entry['board_section'] ?? ''))],
                        [
                            'legal_case_id' => $case['id'],
                            'item_number' => $entry['item'],
                            'case_reference' => $entry['reference'],
                            'court_room' => $entry['court'],
                            'judge_name' => implode(' · ', $entry['judges']) ?: null,
                            'source_line' => $entry['line'],
                            'match_status' => 'matched',
                            'matched_at' => now(),
                            'metadata' => ['vc_link' => $entry['vc_link'], 'list' => $document->document_label]
                                + array_intersect_key($entry, ['board_section' => true]) + $place,
                        ],
                    );
                }
            }
        });

        foreach ($matches as $entry) {
            $place = self::placeOnBoard($sectionBoards[$entry['board_section'] ?? ''] ?? $boards, $entry);

            foreach ($casesByKey[$entry['key']] as $case) {
                $this->addListedDate($case, $entry, $date, $place['board_total'] ?: null, (string) $document->document_label);
                $this->tellOwner($courtShort, $case, $entry, $date);
            }
        }

        if ($report) {
            $report($date->format('d M').' · '.$listing['title'].': '.count($entries).' matters, '.$matches->count().' of ours.');
        }

        return $matches->count();
    }

    /**
     * The item numbers on each court's board in one list, in hearing order ("1", "2", "2.1", "3"…).
     * Connected matters printed under one item ("WITH …") count once.
     *
     * @param  list<array{item: string, court: ?string}>  $entries
     * @return array<string, list<string>>
     */
    public static function boards(array $entries, bool $numericOrder = true): array
    {
        $boards = [];

        foreach ($entries as $entry) {
            $boards[(string) $entry['court']][$entry['item']] = true;
        }

        return array_map(function (array $items) use ($numericOrder): array {
            $items = array_keys($items);
            if ($numericOrder) {
                usort($items, fn ($a, $b): int => (float) $a <=> (float) $b);
            }

            return array_map('strval', $items);
        }, $boards);
    }

    /**
     * Where an item sits on its court's board in this list: how many cases the board has, its first and last
     * item, and the item's place in the order the court hears them.
     *
     * @param  array<string, list<string>>  $boards
     * @param  array{item: string, court: ?string}  $entry
     *                                                      The position counts main items only: connected matters (item 2.1, 2.2) are heard with item 2.
     * @return array{board_total: int, board_items: int, board_from: ?string, board_to: ?string, board_position: ?int}
     */
    public static function placeOnBoard(array $boards, array $entry): array
    {
        $items = $boards[(string) $entry['court']] ?? [];
        $main = array_values(array_unique(array_map(fn (string $item): int => (int) $item, $items)));
        $position = array_search((int) $entry['item'], $main, true);

        return [
            'board_total' => count($items),
            'board_items' => count($main),
            'board_from' => $items[0] ?? null,
            'board_to' => $items ? end($items) : null,
            'board_position' => $position === false ? null : $position + 1,
        ];
    }

    public function textOf(string $pdfContents): string
    {
        return (new Parser)->parseContent($pdfContents)->getText();
    }

    /**
     * Saved, open cases (all advocates) chosen by $courtCases, keyed by case number (and diary number).
     *
     * @param  Closure(Builder<LegalCase>): void  $courtCases
     * @return array<string, list<array{id: int, user_id: int, title: string}>>
     */
    public function casesByKey(Closure $courtCases): array
    {
        $query = LegalCase::query()->withoutGlobalScopes()->whereNull('deleted_at')->whereNull('closed_at')->whereNotNull('user_id');
        $courtCases($query);
        $byKey = [];

        foreach ($query->get(['id', 'user_id', 'case_number', 'case_type', 'case_title', 'diary_number']) as $case) {
            $entry = ['id' => $case->id, 'user_id' => $case->user_id, 'title' => (string) $case->case_title];

            if ($key = DelhiHighCourtCauseList::keyForCase($case->case_number, $case->case_type)) {
                $byKey[$key][] = $entry;
            }

            if (preg_match('/(\d{1,7})\s*[\/-]\s*((?:19|20)\d{2})/', (string) $case->diary_number, $diary)) {
                $byKey[self::diaryKey($diary[1], $diary[2])][] = $entry;
            }
        }

        return $byKey;
    }

    public static function diaryKey(string $number, string $year): string
    {
        return 'DIARY-'.((int) $number).'-'.$year;
    }

    public function ensureSource(string $provider, string $name, string $url): void
    {
        CourtDataSource::query()->firstOrCreate(['provider_key' => $provider], [
            'provider_name' => $name, 'source_type' => 'cause_list', 'base_url' => $url, 'is_enabled' => true,
        ]);
    }

    /**
     * A case listed for today or a later day that has no date ahead in the diary gets this listing as its next
     * date, with the court room, judges and item from the list. A date the advocate already has is left alone.
     *
     * @param  array{id: int, user_id: int, title: string}  $case
     * @param  array{item: string, court: ?string, judges: list<string>, vc_link: ?string}  $entry
     */
    private function addListedDate(array $case, array $entry, Carbon $date, ?int $listTotal, string $listName): void
    {
        if ($date->lt(today())) {
            return;
        }

        $today = today()->toDateString();
        $hasDateAhead = CourtHearing::query()->withoutGlobalScopes()
            ->where('case_id', $case['id'])
            ->where(fn ($dates) => $dates->where('hearing_date', '>=', $today)->orWhere('next_hearing_date', '>=', $today))
            ->exists();

        if ($hasDateAhead) {
            return;
        }

        $hearing = new CourtHearing([
            'case_id' => $case['id'],
            'hearing_date' => $date->toDateString(),
            'court_room' => $entry['court'],
            'item_number' => $entry['item'],
            'list_total' => $listTotal,
            'judge_name' => implode(' · ', $entry['judges']) ?: null,
            'vc_link' => $entry['vc_link'],
            'source_type' => 'cause_list',
            'source_reference' => mb_substr($listName, 0, 250) ?: null,
        ]);
        $hearing->user_id = $case['user_id'];
        $hearing->save();
    }

    /**
     * @param  array{id: int, user_id: int, title: string}  $case
     * @param  array{reference: string, item: string, court: ?string}  $entry
     */
    private function tellOwner(string $courtShort, array $case, array $entry, Carbon $date): void
    {
        $owner = User::query()->find($case['user_id']);
        $key = 'causelist:'.$case['id'].':'.$date->toDateString();

        if (! $owner || $owner->notifications()->where('data->key', $key)->exists()) {
            return;
        }

        $when = $date->isToday() ? 'today' : ($date->isTomorrow() ? 'tomorrow' : $date->format('D, d M'));

        $owner->notify(new ReminderNotification(
            kind: 'hearing',
            title: 'Listed '.$when.' · '.$courtShort.($entry['court'] ? ' · Court '.$entry['court'] : '').' · Item '.$entry['item'],
            body: $entry['reference'].($case['title'] ? ' · '.$case['title'] : ''),
            url: route('cases.show', $case['id']).'#cause-list',
            key: $key,
        ));
    }
}
