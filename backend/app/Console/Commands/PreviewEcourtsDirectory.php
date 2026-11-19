<?php

namespace App\Console\Commands;

use App\Providers\EcourtsDistrictDirectoryProvider;
use Illuminate\Console\Command;
use Throwable;

class PreviewEcourtsDirectory extends Command
{
    protected $signature = 'ecourts:preview-directory';

    protected $description = 'Preview State and UT entries from the official eCourts directory';

    public function handle(EcourtsDistrictDirectoryProvider $provider): int
    {
        $this->info('Fetching the official eCourts directory...');

        try {
            $records = $provider->fetch('state_directory');

            $rows = [];

            foreach ($records as $record) {
                $rows[] = [
                    $record->externalId,
                    $record->attributes['display_name'] ?? '',
                    $record->sourceUrl,
                ];
            }

            if (count($rows) === 0) {
                $this->error('No directory entries were extracted.');
                return self::FAILURE;
            }

            $this->table(
                ['Official directory ID', 'State / UT label', 'Source URL'],
                $rows
            );

            $this->newLine();
            $this->info('Total entries extracted: ' . count($rows));
            $this->warn('Preview only. No database records were created or modified.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Directory preview failed: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }
}