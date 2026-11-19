<?php

namespace App\Console\Commands;

use App\Models\ApiSyncError;
use App\Models\ApiSyncRun;
use App\Models\CourtDataSource;
use App\Models\SyncedCourtDataRecord;
use App\Providers\EcourtsDistrictDirectoryProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncEcourtsDirectory extends Command
{
    protected $signature = 'ecourts:sync-directory';

    protected $description = 'Synchronise State and UT directory entries from the official eCourts website';

    public function handle(EcourtsDistrictDirectoryProvider $provider): int
    {
        $providerKey = $provider->providerKey();
        $entityType = 'state_directory';

        $source = CourtDataSource::where('provider_key', $providerKey)->first();

        if (!$source) {
            $this->error('eCourts source is not registered.');
            $this->line('Run: php artisan ecourts:register-source');
            return self::FAILURE;
        }

        if (!$source->is_enabled) {
            $this->error('eCourts source is disabled. Enable it only after reviewing its source configuration.');
            return self::FAILURE;
        }

        $run = ApiSyncRun::create([
            'provider_key' => $providerKey,
            'entity_type' => $entityType,
            'sync_mode' => 'full',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $errors = 0;
        $received = 0;

        try {
            $records = $provider->fetch($entityType);

            foreach ($records as $record) {
                $received++;

                try {
                    if ($record->entityType !== $entityType || trim($record->externalId) === '') {
                        throw new \UnexpectedValueException('Invalid directory record.');
                    }

                    $attributes = $record->attributes;

                    $storedAttributes = array_merge($attributes, [
                        '_raw_payload' => $record->rawPayload,
                    ]);

                    $payloadHash = hash(
                        'sha256',
                        json_encode(
                            $storedAttributes,
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES |
                            JSON_PARTIAL_OUTPUT_ON_ERROR
                        )
                    );

                    DB::transaction(function () use (
                        $providerKey,
                        $entityType,
                        $record,
                        $storedAttributes,
                        $payloadHash,
                        &$created,
                        &$updated,
                        &$unchanged
                    ) {
                        $existing = SyncedCourtDataRecord::query()
                            ->where('provider_key', $providerKey)
                            ->where('entity_type', $entityType)
                            ->where('external_id', $record->externalId)
                            ->lockForUpdate()
                            ->first();

                        if (!$existing) {
                            SyncedCourtDataRecord::create([
                                'provider_key' => $providerKey,
                                'entity_type' => $entityType,
                                'external_id' => $record->externalId,
                                'display_name' => $record->attributes['display_name'] ?? null,
                                'attributes' => $storedAttributes,
                                'source_url' => $record->sourceUrl,
                                'source_updated_at' => $record->sourceUpdatedAt,
                                'fetched_at' => now(),
                                'payload_hash' => $payloadHash,
                                'record_status' => 'active',
                            ]);

                            $created++;
                            return;
                        }

                        if ($existing->payload_hash === $payloadHash) {
                            $existing->update([
                                'fetched_at' => now(),
                            ]);

                            $unchanged++;
                            return;
                        }

                        $existing->update([
                            'display_name' => $record->attributes['display_name'] ?? null,
                            'attributes' => $storedAttributes,
                            'source_url' => $record->sourceUrl,
                            'source_updated_at' => $record->sourceUpdatedAt,
                            'fetched_at' => now(),
                            'payload_hash' => $payloadHash,
                            'record_status' => 'active',
                        ]);

                        $updated++;
                    });
                } catch (Throwable $exception) {
                    $errors++;

                    ApiSyncError::create([
                        'api_sync_run_id' => $run->id,
                        'external_id' => $record->externalId ?? null,
                        'error_code' => 'ECOURTS_DIRECTORY_RECORD_ERROR',
                        'message' => $exception->getMessage(),
                        'context' => [
                            'provider_key' => $providerKey,
                            'entity_type' => $entityType,
                        ],
                    ]);
                }
            }

            if ($received === 0) {
                throw new \RuntimeException('The directory returned no records.');
            }

            $run->update([
                'status' => $errors > 0 ? 'completed_with_errors' : 'completed',
                'finished_at' => now(),
                'records_received' => $received,
                'records_created' => $created,
                'records_updated' => $updated,
                'records_skipped' => $unchanged,
                'metadata' => [
                    'entity_type' => $entityType,
                    'records_unchanged' => $unchanged,
                    'record_errors' => $errors,
                    'deactivation_performed' => false,
                    'source_url' => 'https://ecourts.gov.in/ecourts2.0/?p=dist_court',
                ],
            ]);

            $source->update(['last_synced_at' => now()]);

            $this->table(
                ['Result', 'Count'],
                [
                    ['Received', $received],
                    ['Created', $created],
                    ['Updated', $updated],
                    ['Unchanged', $unchanged],
                    ['Errors', $errors],
                ]
            );

            $this->info('eCourts directory synchronisation finished.');
            $this->warn('Missing records were not deactivated or deleted.');

            return $errors > 0 ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'records_received' => $received,
                'records_created' => $created,
                'records_updated' => $updated,
                'records_skipped' => $unchanged,
                'error_message' => $exception->getMessage(),
            ]);

            ApiSyncError::create([
                'api_sync_run_id' => $run->id,
                'error_code' => 'ECOURTS_DIRECTORY_SYNC_FAILED',
                'message' => $exception->getMessage(),
                'context' => ['provider_key' => $providerKey],
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}