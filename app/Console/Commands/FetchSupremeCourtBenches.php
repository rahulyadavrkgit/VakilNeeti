<?php

namespace App\Console\Commands;

use App\Services\SupremeCourtBenches;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Reads who sits in each Supreme Court room, today and tomorrow (the list comes out the evening before),
 * so hearing cards name the bench of their own date.
 */
#[Signature('courts:supreme-court-benches {--date= : One date (YYYY-MM-DD) instead of today and tomorrow}')]
#[Description('Save the Supreme Court bench sitting in each court room, by date')]
class FetchSupremeCourtBenches extends Command
{
    public function handle(SupremeCourtBenches $benches): int
    {
        $dates = $this->option('date')
            ? [Carbon::parse((string) $this->option('date'))->startOfDay()]
            : [today(), today()->addDay()];

        foreach ($dates as $date) {
            $found = $benches->fetch($date);

            // Nothing published (a holiday, or the list is not out yet): keep what was saved before.
            if ($found === []) {
                $this->line($date->format('d M Y').': no benches listed yet.');

                continue;
            }

            $benches->save($date, $found);
            $this->info($date->format('d M Y').': '.count($found).' courts sitting.');
        }

        return self::SUCCESS;
    }
}
