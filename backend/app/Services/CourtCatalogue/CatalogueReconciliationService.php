<?php

namespace App\Services\CourtCatalogue;

use App\Models\ApiSyncError;
use App\Models\Court;
use App\Models\CourtEstablishment;
use App\Models\ExternalRecordMapping;
use App\Models\JudicialDistrict;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class CatalogueReconciliationService
{
    private const MODELS = [
        'courts' => Court::class,
        'judicial_districts' => JudicialDistrict::class,
        'court_establishments' => CourtEstablishment::class,
    ];

    private const IDENTIFIERS = [
        'courts' => 'court_code',
        'judicial_districts' => 'district_code',
        'court_establishments' => 'establishment_code',
    ];

    public function __construct(
        private readonly CatalogueChangeLogger $logger
    ) {}

    public function reconcile(
        string $providerKey,
        string $entityType,
        string $localTable,
        iterable $records,
        ?int $syncRunId = null,
        string $sourceName = 'Official government source',
        string $sourceStatus = 'pending_verification',
        bool $allowCreate = false
    ): array {
        if (!isset(self::MODELS[$localTable])) {
            throw new InvalidArgumentException('Unsupported catalogue table.');
        }

        if (!in_array($sourceStatus, ['pending_verification', 'verified'], true)) {
            throw new InvalidArgumentException('Invalid source verification status.');
        }

        $modelClass = self::MODELS[$localTable];
        $identifierField = self::IDENTIFIERS[$localTable];

        $stats = [
            'received' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($records as $record) {
            $stats['received']++;

            try {
                if (!is_array($record)) {
                    throw new InvalidArgumentException('Record must be an array.');
                }

                $externalId = trim((string) ($record['external_id'] ?? ''));

                if ($externalId === '') {
                    throw new InvalidArgumentException('Official external ID is missing.');
                }

                $attributes = $record['attributes'] ?? [];

                if (!is_array($attributes) || empty($attributes[$identifierField])) {
                    throw new InvalidArgumentException(
                        'A stable official catalogue code is required.'
                    );
                }

                if (
                    $localTable === 'judicial_districts' &&
                    empty($attributes['state_id'])
                ) {
                    throw new InvalidArgumentException(
                        'A state_id is required to identify a judicial district safely.'
                    );
                }

                if (
                    $localTable === 'court_establishments' &&
                    empty($attributes['court_id'])
                ) {
                    throw new InvalidArgumentException(
                        'A court_id is required to identify an establishment safely.'
                    );
                }

                $sourceUrl = $record['source_url'] ?? null;

                if (
                    $sourceUrl !== null &&
                    !filter_var($sourceUrl, FILTER_VALIDATE_URL)
                ) {
                    throw new InvalidArgumentException('Invalid source URL.');
                }

                $result = DB::transaction(function () use (
                    $providerKey,
                    $entityType,
                    $localTable,
                    $modelClass,
                    $identifierField,
                    $externalId,
                    $attributes,
                    $sourceUrl,
                    $sourceName,
                    $sourceStatus,
                    $syncRunId,
                    $allowCreate,
                    $record
                ) {
                    $mapping = ExternalRecordMapping::query()
                        ->where('provider_key', $providerKey)
                        ->where('entity_type', $entityType)
                        ->where('external_id', $externalId)
                        ->lockForUpdate()
                        ->first();

                    $model = null;

                    if ($mapping) {
                        if ($mapping->local_table !== $localTable) {
                            throw new InvalidArgumentException(
                                'External record is already mapped to another catalogue table.'
                            );
                        }

                        $model = $modelClass::query()
                            ->whereKey($mapping->local_record_id)
                            ->lockForUpdate()
                            ->first();

                        if (!$model) {
                            throw new InvalidArgumentException(
                                'Mapped catalogue record is missing or soft-deleted. Manual review required.'
                            );
                        }
                    } else {
                        $existingQuery = $modelClass::query()
                            ->where($identifierField, $attributes[$identifierField]);

                        if ($localTable === 'judicial_districts') {
                            $existingQuery->where('state_id', $attributes['state_id']);
                        }

                        if ($localTable === 'court_establishments') {
                            $existingQuery->where('court_id', $attributes['court_id']);
                        }

                        $model = $existingQuery->lockForUpdate()->first();

                        if (!$model && !$allowCreate) {
                            return 'skipped';
                        }

                        if (!$model) {
                            $model = new $modelClass();
                        }
                    }

                    $safeAttributes = [];

                    foreach ($attributes as $field => $value) {
                        if (in_array($field, $model->getFillable(), true)) {
                            $safeAttributes[$field] = $value;
                        }
                    }

                    if (in_array('source_url', $model->getFillable(), true)) {
                        $safeAttributes['source_url'] = $sourceUrl;
                    }

                    if (in_array('source_name', $model->getFillable(), true)) {
                        $safeAttributes['source_name'] = $sourceName;
                    }

                    if (in_array('source_status', $model->getFillable(), true)) {
                        $safeAttributes['source_status'] = $sourceStatus;
                    }

                    if (
                        in_array('last_verified_at', $model->getFillable(), true) &&
                        $sourceStatus === 'verified'
                    ) {
                        $safeAttributes['last_verified_at'] = now();
                    }

                    $wasExisting = $model->exists;

                    $model->fill($safeAttributes);
                    $changed = $model->isDirty();

                    $this->logger->save(
                        $model,
                        $safeAttributes,
                        $providerKey,
                        $entityType,
                        $externalId,
                        $sourceUrl,
                        $sourceName,
                        $syncRunId
                    );

                    $mappingAttributes = [
                        'source_url' => $sourceUrl,
                        'source_updated_at' => $record['source_updated_at'] ?? null,
                        'last_synced_at' => now(),
                        'payload_hash' => hash(
                            'sha256',
                            json_encode(
                                $attributes,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            )
                        ),
                    ];

                    if (!$mapping) {
                        ExternalRecordMapping::create(array_merge(
                            $mappingAttributes,
                            [
                                'provider_key' => $providerKey,
                                'entity_type' => $entityType,
                                'local_table' => $localTable,
                                'local_record_id' => $model->getKey(),
                                'external_id' => $externalId,
                                'external_parent_id' => $record['external_parent_id'] ?? null,
                                'metadata' => [
                                    'source_name' => $sourceName,
                                    'source_status' => $sourceStatus,
                                ],
                            ]
                        ));
                    } else {
                        $mapping->update($mappingAttributes);
                    }

                    if (!$wasExisting) {
                        return 'created';
                    }

                    return $changed ? 'updated' : 'unchanged';
                });

                $stats[$result]++;
            } catch (Throwable $exception) {
                $stats['errors']++;

                if ($syncRunId) {
                    ApiSyncError::create([
                        'api_sync_run_id' => $syncRunId,
                        'external_id' => is_array($record)
                            ? ($record['external_id'] ?? null)
                            : null,
                        'error_code' => 'CATALOGUE_RECONCILIATION_ERROR',
                        'message' => $exception->getMessage(),
                        'context' => [
                            'provider_key' => $providerKey,
                            'entity_type' => $entityType,
                            'local_table' => $localTable,
                        ],
                    ]);
                }
            }
        }

        return $stats;
    }
}