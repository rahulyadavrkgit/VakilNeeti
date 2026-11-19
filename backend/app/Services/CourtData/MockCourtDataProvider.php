<?php

namespace App\Services\CourtData;

use App\Contracts\CourtDataProvider;

class MockCourtDataProvider implements CourtDataProvider
{
    /**
     * This provider is for development testing only.
     * It does not retrieve official court information.
     */
    public function providerKey(): string
    {
        return 'mock';
    }

    public function providerName(): string
    {
        return 'VakilNeeti Mock Development Provider';
    }

    public function supports(string $entityType): bool
    {
        $supportedTypes = [
            'state',
            'court_type',
            'court',
            'bench',
            'judicial_district',
            'establishment',
            'courtroom',
            'judge',
            'roster',
            'case',
            'case_party',
            'advocate',
            'hearing',
            'order',
            'judgment',
            'cause_list',
            'display_board',
            'court_calendar',
            'tribunal',
        ];

        return in_array($entityType, $supportedTypes, true);
    }

    public function fetch(
        string $entityType,
        array $filters = []
    ): iterable {
        if (!$this->supports($entityType)) {
            throw new \InvalidArgumentException(
                "Unsupported entity type: {$entityType}"
            );
        }

        // No real data is returned by the mock provider.
        // Real providers will be connected after access is approved.
        return [];
    }
}
