<?php

namespace App\Services\CourtData;

use App\Data\CourtDataRecord;
use App\Models\ApiSyncError;
use App\Models\ApiSyncRun;
use App\Models\CourtDataSource;
use App\Models\SyncedCourtDataRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

class CourtDataSyncService
{
    public function __construct(
        private CourtDataProviderRegistry $registry
    ) {
    }

    public function sync(
        string $providerKey,
        string $entityType,
        array $filters = []
    ): ApiSyncRun {

        $source = CourtDataSource::where(
            'provider_key',
            $providerKey
        )->firstOrFail();

        if (!$source->is_enabled) {
            throw new InvalidArgumentException(
                "Data provider '{$providerKey}' is disabled."
            );
        }

        $provider = $this->registry->get($providerKey);

        if (!$provider->supports($entityType)) {
            throw new InvalidArgumentException(
                "Provider '{$providerKey}' does not support '{$entityType}'."
            );
        }

        $run = ApiSyncRun::create([
            'provider_key' => $providerKey,
            'entity_type' => $entityType,
            'sync_mode' => 'full',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $received = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        try {

            foreach ($provider->fetch($entityType, $filters) as $record) {

                $received++;

                try {

                    if (!$record instanceof CourtDataRecord) {
                        throw new InvalidArgumentException(
                            'Provider returned an invalid data record.'
                        );
                    }

                    if ($record->entityType !== $entityType) {
                        throw new InvalidArgumentException(
                            'Provider returned an unexpected entity type.'
                        );
                    }

                    $externalId = trim($record->externalId);

                    if ($externalId === '') {
                        throw new InvalidArgumentException(
                            'External record ID is missing.'
                        );
                    }

                    $attributes = $record->attributes;

                    $displayName = $attributes['display_name']
                        ?? $attributes['name']
                        ?? $attributes['court_name']
                        ?? $attributes['case_number']
                        ?? $attributes['title']
                        ?? null;

                    if (!is_string($displayName) && !is_numeric($displayName)) {
                        $displayName = null;
                    }

                    $normalisedHashData = [
                        'entity_type' => $record->entityType,
                        'external_id' => $externalId,
                        'external_parent_id' => $record->attributes['parent_external_id']
                            ?? null,
                        'attributes' => $attributes,
                        'source_url' => $record->sourceUrl,
                        'source_updated_at' => $record->sourceUpdatedAt,
                    ];

                    $encoded = json_encode(
                        $normalisedHashData,
                        JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                            | JSON_PRESERVE_ZERO_FRACTION
                            | JSON_THROW_ON_ERROR
                    );

                    $payloadHash = hash('sha256', $encoded);

                    $sourceUpdatedAt = null;

                    if ($record->sourceUpdatedAt) {
                        try {
                            $sourceUpdatedAt = Carbon::parse(
                                $record->sourceUpdatedAt
                            );
                        } catch (Throwable $dateError) {
                            throw new InvalidArgumentException(
                                'The provider supplied an invalid source update date.'
                            );
                        }
                    }

                    $result = DB::transaction(function () use (
                        $providerKey,
                        $entityType,
                        $externalId,
                        $record,
                        $displayName,
                        $attributes,
                        $sourceUpdatedAt,
                        $payloadHash
                    ) {

                        $existing = SyncedCourtDataRecord::where(
                            'provider_key',
                            $providerKey
                        )
                        ->where('entity_type', $entityType)
                        ->where('external_id', $externalId)
                        ->first();

                        $data = [
                            'external_parent_id' =>
                                $record->attributes['parent_external_id'] ?? null,
                            'display_name' => $displayName,
                            'attributes' => $attributes,
                            'source_url' => $record->sourceUrl,
                            'source_updated_at' => $sourceUpdatedAt,
                            'fetched_at' => now(),
                            'payload_hash' => $payloadHash,
                            'record_status' => 'active',
                        ];

                        if (!$existing) {

                            SyncedCourtDataRecord::create(
                                array_merge($data, [
                                    'provider_key' => $providerKey,
                                    'entity_type' => $entityType,
                                    'external_id' => $externalId,
                                ])
                            );

                            return 'created';
                        }

                        if ($existing->payload_hash === $payloadHash) {
                            $existing->update([
                                'fetched_at' => now(),
                            ]);

                            return 'skipped';
                        }

                        $existing->update($data);

                        return 'updated';
                    });

                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }

                } catch (Throwable $recordError) {

                    $errors++;

                    ApiSyncError::create([
                        'api_sync_run_id' => $run->id,
                        'external_id' => $record instanceof CourtDataRecord
                            ? $record->externalId
                            : null,
                        'error_code' => 'RECORD_PROCESSING_ERROR',
                        'message' => $recordError->getMessage(),
                        'context' => [
                            'entity_type' => $entityType,
                        ],
                    ]);

                    Log::warning('Court data record could not be processed.', [
                        'sync_run_id' => $run->id,
                        'provider' => $providerKey,
                        'entity_type' => $entityType,
                        'message' => $recordError->getMessage(),
                    ]);
                }
            }

            $status = $errors > 0
                ? 'completed_with_errors'
                : 'completed';

            $run->update([
                'status' => $status,
                'finished_at' => now(),
                'records_received' => $received,
                'records_created' => $created,
                'records_updated' => $updated,
                'records_skipped' => $skipped,
            ]);

            $source->update([
                'last_synced_at' => now(),
            ]);

        } catch (Throwable $error) {

            ApiSyncError::create([
                'api_sync_run_id' => $run->id,
                'error_code' => 'PROVIDER_SYNC_ERROR',
                'message' => $error->getMessage(),
                'context' => [
                    'entity_type' => $entityType,
                ],
            ]);

            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'records_received' => $received,
                'records_created' => $created,
                'records_updated' => $updated,
                'records_skipped' => $skipped,
                'error_message' => $error->getMessage(),
            ]);

            Log::error('Court data synchronisation failed.', [
                'sync_run_id' => $run->id,
                'provider' => $providerKey,
                'entity_type' => $entityType,
                'message' => $error->getMessage(),
            ]);
        }

        return $run->fresh();
    }
}
