<?php

namespace App\Contracts;

use App\Data\CourtDataRecord;

interface CourtDataProvider
{
    public function providerKey(): string;

    public function providerName(): string;

    public function supports(string $entityType): bool;

    /**
     * Retrieve normalised records from the provider.
     *
     * @return iterable<CourtDataRecord>
     */
    public function fetch(
        string $entityType,
        array $filters = []
    ): iterable;
}
