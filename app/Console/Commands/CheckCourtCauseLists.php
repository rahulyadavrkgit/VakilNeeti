<?php

namespace App\Console\Commands;

use App\Services\CauseLists\AllahabadHighCourtCauseList;
use App\Services\CauseLists\CauseListRecorder;
use App\Services\CauseLists\SupremeCourtCauseList;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Reads the Allahabad High Court's or the Supreme Court's published cause lists and notes every saved case
 * of that court that is listed (court, item, bench, place in the hearing order). The Delhi High Court has
 * its own command (courts:delhi-hc-cause-list). Runs only when someone has a case in that court.
 */
#[Signature('courts:cause-lists {court : allahabad-high-court or supreme-court} {--date= : Only this date (YYYY-MM-DD)} {--when=both : today, tomorrow or both}')]
#[Description('Find saved Allahabad High Court or Supreme Court cases in the court\'s published cause lists')]
class CheckCourtCauseLists extends Command
{
    public function handle(CauseListRecorder $recorder, AllahabadHighCourtCauseList $allahabad, SupremeCourtCauseList $supreme): int
    {
        $court = (string) $this->argument('court');

        [$provider, $name, $short, $url, $reader, $cases] = match ($court) {
            AllahabadHighCourtCauseList::PROVIDER => [
                $court, 'Allahabad High Court', 'Allahabad HC', AllahabadHighCourtCauseList::PAGE_URL, $allahabad,
                // The principal seat only (its CNR starts UPHC01); the Lucknow bench publishes elsewhere.
                fn ($query) => $query->where('court_level', 'high')->where('state_code', 'UP')
                    ->where(fn ($seat) => $seat->whereNull('cnr_number')->orWhere('cnr_number', '')->orWhere('cnr_number', 'like', 'UPHC01%'))
                    ->where('court_name', 'not like', '%Lucknow%'),
            ],
            SupremeCourtCauseList::PROVIDER => [
                $court, 'Supreme Court of India', 'Supreme Court', SupremeCourtCauseList::PAGE_URL, $supreme,
                fn ($query) => $query->where(fn ($sc) => $sc->where('court_level', 'supreme')->orWhere('court_name', 'like', '%Supreme Court%')),
            ],
            default => [null, null, null, null, null, null],
        };

        if (! $provider) {
            $this->error('Choose allahabad-high-court or supreme-court.');

            return self::FAILURE;
        }

        $casesByKey = $recorder->casesByKey($cases);

        if ($casesByKey === []) {
            $this->info("No saved {$name} cases to look for.");

            return self::SUCCESS;
        }

        $dates = match (true) {
            (bool) $this->option('date') => [Carbon::parse($this->option('date'))->startOfDay()],
            $this->option('when') === 'today' => [today()],
            $this->option('when') === 'tomorrow' => [today()->addDay()],
            default => [today(), today()->addDay()],
        };

        @ini_set('memory_limit', '1024M');
        $recorder->ensureSource($provider, $name, $url);
        $found = 0;

        foreach ($dates as $date) {
            $documents = $reader->documentsFor($date);

            if ($documents === []) {
                $this->line($date->format('d M').': no list published yet.');
            }

            foreach ($documents as $listing) {
                $found += $recorder->check($provider, $short, $name, $listing, $date, $casesByKey,
                    fn (string $pdf): array => CauseListRecorder::withExcerpts($text = $recorder->textOf($pdf), $reader->entries($text)),
                    fn (string $line) => $this->line($line));
            }
        }

        $this->info("Found {$found} listings of saved cases.");

        return self::SUCCESS;
    }
}
