<?php

namespace App\Console\Commands;

use App\Services\CourtData\CourtDataSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncCourtData extends Command
{
    protected $signature = 'court-data:sync
                            {provider : Registered provider key}
                            {entity : Entity type to synchronise}
                            {--filters= : Optional JSON filters}';

    protected $description = 'Synchronise court records from a registered data provider';

    public function handle(CourtDataSyncService $service): int
    {
        $provider = $this->argument('provider');
        $entity = $this->argument('entity');

        $filters = [];

        $rawFilters = $this->option('filters');

        if ($rawFilters !== null && $rawFilters !== '') {
            $filters = json_decode($rawFilters, true);

            if (!is_array($filters)) {
                $this->error('The filters option must contain valid JSON.');

                return self::FAILURE;
            }
        }

        try {
            $run = $service->sync(
                $provider,
                $entity,
                $filters
            );
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        $this->info('Court data synchronisation result');

        $this->table(
            ['Metric', 'Result'],
            [
                ['Run ID', $run->id],
                ['Provider', $run->provider_key],
                ['Entity', $run->entity_type],
                ['Status', $run->status],
                ['Records received', $run->records_received],
                ['Records created', $run->records_created],
                ['Records updated', $run->records_updated],
                ['Records skipped', $run->records_skipped],
                ['Started at', $run->started_at],
                ['Finished at', $run->finished_at],
            ]
        );

        if ($run->error_message) {
            $this->error($run->error_message);
        }

        if ($run->status === 'failed') {
            return self::FAILURE;
        }

        if ($run->status === 'completed_with_errors') {
            $this->warn('Synchronisation completed with some record errors.');

            return self::FAILURE;
        }

        $this->info('Synchronisation completed.');

        return self::SUCCESS;
    }
}
