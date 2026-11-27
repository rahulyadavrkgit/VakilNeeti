<?php

namespace App\Console\Commands;

use App\Services\DistrictCourtDocuments;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Twice a day: the latest bail roster and duty magistrate roster of every Delhi district, found and copied here,
 * so they open at once inside VakilNiti.
 */
#[Signature('courts:district-documents')]
#[Description('Find and keep the latest bail roster and duty magistrate PDF of each Delhi district')]
class FetchDistrictCourtDocuments extends Command
{
    public function handle(DistrictCourtDocuments $documents): int
    {
        foreach (array_keys(DistrictCourtDocuments::districts()) as $district) {
            foreach (array_keys(DistrictCourtDocuments::KINDS) as $kind) {
                $found = $documents->latest($district, $kind, fresh: true);
                $saved = $found ? $documents->pdfPath($district, $kind, $found) : null;
                $this->line(str_pad($district, 30).str_pad($kind, 17).($found ? ($saved ? '✓ ' : '! not copied · ').$found['title'] : '– none found'));
            }
        }

        return self::SUCCESS;
    }
}
