<?php

namespace App\Services\CourtCatalogue;

use App\Models\CourtCatalogueChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTimeInterface;
use InvalidArgumentException;

class CatalogueChangeLogger
{
    public function save(
        Model $model,
        array $attributes,
        string $providerKey,
        string $entityType,
        ?string $externalId = null,
        ?string $sourceUrl = null,
        ?string $sourceName = null,
        ?int $syncRunId = null
    ): Model {
        if (!$model->getTable()) {
            throw new InvalidArgumentException('A valid catalogue model is required.');
        }

        return DB::transaction(function () use (
            $model,
            $attributes,
            $providerKey,
            $entityType,
            $externalId,
            $sourceUrl,
            $sourceName,
            $syncRunId
        ) {
            $oldValues = [];

            foreach ($attributes as $field => $value) {
                if (!in_array($field, $model->getFillable(), true)) {
                    continue;
                }

                $oldValues[$field] = $model->exists
                    ? $model->getAttribute($field)
                    : null;
            }

            $model->fill($attributes);
            $model->save();

            foreach ($oldValues as $field => $oldValue) {
                $newValue = $model->getAttribute($field);

                if ($this->normalise($oldValue) === $this->normalise($newValue)) {
                    continue;
                }

                CourtCatalogueChangeLog::create([
                    'provider_key' => $providerKey,
                    'entity_type' => $entityType,
                    'local_table' => $model->getTable(),
                    'local_record_id' => $model->getKey(),
                    'external_id' => $externalId,
                    'field_name' => $field,
                    'old_value' => ['value' => $oldValue],
                    'new_value' => ['value' => $newValue],
                    'source_url' => $sourceUrl,
                    'source_name' => $sourceName,
                    'api_sync_run_id' => $syncRunId,
                    'changed_at' => now(),
                ]);
            }

            return $model;
        });
    }

    private function normalise(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PARTIAL_OUTPUT_ON_ERROR
        ) ?: '';
    }
}