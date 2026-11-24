<?php

namespace App\Console\Commands;

use App\Models\CauseListAppearance;
use App\Models\CourtDataSource;
use App\Models\CourtDocument;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\DelhiHighCourtCauseList;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Morning and evening: read the Delhi High Court's cause lists for today and the next two days, and note
 * every saved Delhi HC case that is listed (court, item, bench, VC link). The advocate sees it on Home and
 * on the case page, and gets one note in the bell per case and date.
 */
#[Signature('courts:delhi-hc-cause-list {--date= : Only this date (YYYY-MM-DD)} {--days=3 : Today and the next days to check}')]
#[Description('Find saved Delhi High Court cases in the court\'s published cause lists')]
class CheckDelhiHighCourtCauseList extends Command
{
    public const PROVIDER = 'delhi-high-court';

    /** A cause list larger than this is not downloaded (the biggest combined lists are a few MB). */
    private const MAX_PDF_BYTES = 40_000_000;

    public function handle(DelhiHighCourtCauseList $causeList): int
    {
        $casesByKey = $this->delhiCasesByKey();

        if ($casesByKey === []) {
            $this->info('No saved Delhi High Court cases to look for.');

            return self::SUCCESS;
        }

        $dates = $this->option('date')
            ? [Carbon::parse($this->option('date'))->startOfDay()]
            : collect(range(0, max(1, (int) $this->option('days')) - 1))->map(fn (int $days): Carbon => today()->addDays($days))->all();
        $pages = $causeList->listingPages(collect($dates)->min());

        if ($pages === null) {
            $this->warn('The cause-list page did not open.');

            return self::SUCCESS;
        }

        @ini_set('memory_limit', '1024M');
        $this->ensureSource();
        $found = 0;

        foreach ($dates as $date) {
            foreach ($causeList->documentsInPage($pages, $date) as $document) {
                $found += $this->checkDocument($causeList, $document, $date, $casesByKey);
            }
        }

        $this->info("Found {$found} listings of saved cases.");

        return self::SUCCESS;
    }

    /**
     * @param  array{title: string, url: string}  $listing
     * @param  array<string, list<array{id: int, user_id: int}>>  $casesByKey
     */
    private function checkDocument(DelhiHighCourtCauseList $causeList, array $listing, Carbon $date, array $casesByKey): int
    {
        $document = CourtDocument::query()->firstOrNew([
            'provider_key' => self::PROVIDER,
            'document_key' => sha1($listing['url']),
        ]);

        // The court gives a corrected list a new file name, so a list read once does not change.
        if ($document->exists && $document->extraction_status === 'done') {
            return 0;
        }

        $document->fill([
            'listing_date' => $date->toDateString(),
            'court_scope' => 'High Court of Delhi',
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

            $document->fill([
                'download_status' => 'done',
                'mime_type' => 'application/pdf',
                'file_size' => strlen($pdf->body()),
                'checksum' => hash('sha256', $pdf->body()),
            ]);

            $entries = $causeList->entries($causeList->textOf($pdf->body()));
        } catch (Throwable $exception) {
            // A warning, not an error: one unreadable list should not raise the morning error alert every day.
            Log::warning('Delhi HC cause list could not be read', ['url' => $listing['url'], 'message' => $exception->getMessage()]);
            $document->fill(['extraction_status' => 'failed'])->save();

            return 0;
        }

        $matches = collect($entries)->filter(fn (array $entry): bool => isset($casesByKey[$entry['key']]));

        DB::transaction(function () use ($document, $matches, $casesByKey, $entries) {
            $document->fill(['extraction_status' => 'done', 'extracted_at' => now(), 'metadata' => ['entries' => count($entries)]])->save();
            $document->appearances()->delete();

            foreach ($matches as $entry) {
                foreach ($casesByKey[$entry['key']] as $case) {
                    CauseListAppearance::query()->updateOrCreate(
                        ['court_document_id' => $document->id, 'record_key' => sha1($case['id'].'|'.$entry['key'].'|'.$entry['item'].'|'.$entry['court'])],
                        [
                            'legal_case_id' => $case['id'],
                            'item_number' => $entry['item'],
                            'case_reference' => $entry['reference'],
                            'court_room' => $entry['court'],
                            'judge_name' => implode(' · ', $entry['judges']) ?: null,
                            'source_line' => $entry['line'],
                            'match_status' => 'matched',
                            'matched_at' => now(),
                            'metadata' => ['vc_link' => $entry['vc_link'], 'list' => $document->document_label],
                        ],
                    );
                }
            }
        });

        foreach ($matches as $entry) {
            foreach ($casesByKey[$entry['key']] as $case) {
                $this->tellOwner($case, $entry, $date);
            }
        }

        $this->line($date->format('d M').' · '.$listing['title'].': '.count($entries).' matters, '.$matches->count().' of ours.');

        return $matches->count();
    }

    /**
     * @param  array{id: int, user_id: int, title: string}  $case
     * @param  array{reference: string, item: string, court: ?string}  $entry
     */
    private function tellOwner(array $case, array $entry, Carbon $date): void
    {
        $owner = User::query()->find($case['user_id']);
        $key = 'causelist:'.$case['id'].':'.$date->toDateString();

        if (! $owner || $owner->notifications()->where('data->key', $key)->exists()) {
            return;
        }

        $when = $date->isToday() ? 'today' : ($date->isTomorrow() ? 'tomorrow' : $date->format('D, d M'));

        $owner->notify(new ReminderNotification(
            kind: 'hearing',
            title: 'Listed '.$when.' · Delhi HC'.($entry['court'] ? ' Court '.$entry['court'] : '').' · Item '.$entry['item'],
            body: $entry['reference'].($case['title'] ? ' · '.$case['title'] : ''),
            url: route('cases.show', $case['id']).'#cause-list',
            key: $key,
        ));
    }

    /**
     * Every saved, open Delhi High Court case (all advocates), by its case-number key.
     *
     * @return array<string, list<array{id: int, user_id: int, title: string}>>
     */
    private function delhiCasesByKey(): array
    {
        $cases = LegalCase::query()->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNull('closed_at')
            ->whereNotNull('user_id')
            ->where(fn ($query) => $query->where('cnr_number', 'like', 'DLHC%')
                ->orWhere('court_name', 'like', '%High Court of Delhi%')
                ->orWhere('court_name', 'like', '%Delhi High Court%'))
            ->get(['id', 'user_id', 'case_number', 'case_type', 'case_title']);

        $byKey = [];

        foreach ($cases as $case) {
            if ($key = DelhiHighCourtCauseList::keyForCase($case->case_number, $case->case_type)) {
                $byKey[$key][] = ['id' => $case->id, 'user_id' => $case->user_id, 'title' => (string) $case->case_title];
            }
        }

        return $byKey;
    }

    private function ensureSource(): void
    {
        CourtDataSource::query()->firstOrCreate(['provider_key' => self::PROVIDER], [
            'provider_name' => 'High Court of Delhi',
            'source_type' => 'cause_list',
            'base_url' => DelhiHighCourtCauseList::PAGE_URL,
            'is_enabled' => true,
        ]);
    }
}
