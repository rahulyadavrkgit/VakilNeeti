<?php

namespace App\Data;

class CourtDataRecord
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $externalId,
        public readonly array $attributes,
        public readonly ?string $sourceUrl = null,
        public readonly ?string $sourceUpdatedAt = null,
        public readonly array $rawPayload = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'external_id' => $this->externalId,
            'attributes' => $this->attributes,
            'source_url' => $this->sourceUrl,
            'source_updated_at' => $this->sourceUpdatedAt,
            'raw_payload' => $this->rawPayload,
        ];
    }
}
