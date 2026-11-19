<?php

namespace App\Console\Commands;

use App\Contracts\CourtDataProvider;
use App\Models\ApiSyncError;
use App\Models\ApiSyncRun;
use App\Models\CourtDataSource;
use App\Services\CourtCatalogue\CatalogueReconciliationService;
use App\Services\CourtCatalogue\CatalogueSnapshotValidator;
use Illuminate\Console\Command;
use Throwable;

class SyncOfficialCourtCatalogue extends Command
{
    protected $signature = 'court-catalogue:sync
        {provider : Registered provider key}
        {entity : Provider entity type}
        {table : courts, judicial_districts or court_establishments}
        {--allow-create : Permit creation when official identifiers are present}
        {--complete-snapshot : Declare that the provider returned a complete snapshot}';

    protected $description = 'Synchronise verified official records into the court catalogue';

    public function handle(
        CatalogueReconciliationService $reconciler,
        CatalogueSnapshotValidator $validator
    ): int {
        $providerKey = $this->argument('provider');
        $entityType = $this->argument('entity');
        $localTable = $this->argument('table');

        $source = CourtDataSource::where('provider_key', $providerKey)->first();

        if (!$source || !$source->is_enabled) {
            $this->error('Provider source is missing or disabled.');
            return self::FAILURE;
        }

        $providerClass = config("court-data.providers.{$providerKey}");

        if (!$providerClass || !class_exists($providerClass)) {
            $this->error('No provider implementation is configured for this source.');
            $this->line('No records were imported or modified.');
            return self::FAILURE;
        }

        $provider = app($providerClass);

        if (!$provider instanceof CourtDataProvider) {
            $this->error('Configured provider does not implement CourtDataProvider.');
            return self::FAILURE;
        }

        if (!$provider->supports($entityType)) {
            $this->error("Provider does not support entity: {$entityType}");
            return self::FAILURE;
        }

        $run = ApiSyncRun::create([
            'provider_key' => $providerKey,
            'entity_type' => $entityType,
            'sync_mode' => 'full',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $records = [];

            foreach ($provider->fetch($entityType) as $item) {
                if (is_object($item) && method_exists($item, 'toArray')) {
                    $item = $item->toArray();
                }

                if (!is_array($item)) {
                    throw new \UnexpectedValueException(
                        'Provider returned a record in an unsupported format.'
                    );
                }

                $records[] = $item;
            }

            $metadata = $source->metadata ?? [];

            $sourceVerified = ($metadata['source_verified'] ?? false) === true;
            $completeSnapshot = $this->option('complete-snapshot');

            $validation = $validator->validate(
                $records,
                true,
                $completeSnapshot,
                $sourceVerified
            );

            if (!$validation['safe_to_reconcile']) {
                $run->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'records_received' => count($records),
                    'records_skipped' => count($records),
                    'error_message' => implode(' ', $validation['errors']),
                    'metadata' => $validation,
                ]);

                foreach ($validation['errors'] as $error) {
                    ApiSyncError::create([
                        'api_sync_run_id' => $run->id,
                        'error_code' => 'SNAPSHOT_VALIDATION_FAILED',
                        'message' => $error,
                        'context' => ['provider_key' => $providerKey],
                    ]);
                }

                $this->error('Validation failed. Catalogue was not modified.');

                foreach ($validation['errors'] as $error) {
                    $this->line('- ' . $error);
                }

                return self::FAILURE;
            }

            $result = $reconciler->reconcile(
                $providerKey,
                $entityType,
                $localTable,
                $records,
                $run->id,
                $source->provider_name,
                'verified',
                (bool) $this->option('allow-create')
            );

            $hasErrors = $result['errors'] > 0;

            $run->update([
                'status' => $hasErrors ? 'completed_with_errors' : 'completed',
                'finished_at' => now(),
                'records_received' => $result['received'],
                'records_created' => $result['created'],
                'records_updated' => $result['updated'],
                'records_skipped' => $result['skipped'] + $result['unchanged'],
                'metadata' => [
                    'reconciliation' => $result,
                    'snapshot_validation' => $validation,
                ],
            ]);

            $source->update(['last_synced_at' => now()]);

            $this->table(
                ['Metric', 'Count'],
                collect($result)->map(
                    fn ($value, $key) => [$key, $value]
                )->values()->all()
            );

            $this->info('Catalogue synchronisation completed.');

            return $hasErrors ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            ApiSyncError::create([
                'api_sync_run_id' => $run->id,
                'error_code' => 'CATALOGUE_SYNC_FAILED',
                'message' => $exception->getMessage(),
                'context' => ['provider_key' => $providerKey],
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
