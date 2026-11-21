<?php

namespace App\Console\Commands;

use App\Providers\EcourtsDistrictDirectoryProvider;
use Illuminate\Console\Command;
use Throwable;

class PreviewEcourtsDistrictDirectories extends Command
{
    protected $signature = 'ecourts:preview-district-directories';

    protected $description = 'Preview candidate district court directory links';

    public function handle(EcourtsDistrictDirectoryProvider $provider): int
    {
        $this->info('Discovering district court directory candidates...');

        try {
            $records = $provider->fetch('district_directory_candidates');
            $rows = [];

            foreach ($records as $record) {
                $rows[] = [
                    $record->attributes['state_name'] ?? '',
                    $record->attributes['display_name'] ?? '',
                    $record->sourceUrl,
                    $record->externalId,
                ];
            }

            $this->table(
                ['State / UT', 'Link label', 'Candidate URL', 'Candidate ID'],
                $rows
            );

            $this->newLine();
            $this->info('Candidates discovered: ' . count($rows));
            $this->warn('Preview only. No database records were changed.');
            $this->warn('Candidate links have not been verified as official district records.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}