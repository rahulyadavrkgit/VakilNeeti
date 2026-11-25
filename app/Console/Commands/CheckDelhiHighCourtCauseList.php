<?php

namespace App\Console\Commands;

use App\Services\CauseLists\CauseListRecorder;
use App\Services\DelhiHighCourtCauseList;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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

    public function handle(DelhiHighCourtCauseList $causeList, CauseListRecorder $recorder): int
    {
        // Every saved, open Delhi High Court case (all advocates).
        $casesByKey = $recorder->casesByKey(fn ($query) => $query->where(fn ($court) => $court->where('cnr_number', 'like', 'DLHC%')
            ->orWhere(fn ($chosen) => $chosen->where('court_level', 'high')->where('state_code', 'DL'))
            ->orWhere('court_name', 'like', '%High Court of Delhi%')
            ->orWhere('court_name', 'like', '%Delhi High Court%')));

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
        $recorder->ensureSource(self::PROVIDER, 'High Court of Delhi', DelhiHighCourtCauseList::PAGE_URL);
        $found = 0;

        foreach ($dates as $date) {
            foreach ($causeList->documentsInPage($pages, $date) as $listing) {
                $found += $recorder->check(self::PROVIDER, 'Delhi HC', 'High Court of Delhi', $listing, $date, $casesByKey,
                    fn (string $pdf): array => $causeList->entries($causeList->textOf($pdf)),
                    fn (string $line) => $this->line($line));
            }
        }

        $this->info("Found {$found} listings of saved cases.");

        return self::SUCCESS;
    }
}
