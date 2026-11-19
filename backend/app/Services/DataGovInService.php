<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DataGovInService
{
    public static function isValidResourceId(string $resourceId): bool
    {
        $resourceId = trim($resourceId);

        return $resourceId !== ''
            && preg_match('/^[a-f0-9-]{20,80}$/i', $resourceId) === 1;
    }

    public function fetch(string $resourceId, int $offset = 0, int $limit = 100): array
    {
        $apiKey = trim((string) config('datagov.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException(
                'The data.gov.in API key is not configured. Add DATA_GOV_IN_API_KEY to the backend .env file.'
            );
        }

        $resourceId = trim($resourceId);

        if (!self::isValidResourceId($resourceId)) {
            throw new RuntimeException('Invalid data.gov.in resource ID.');
        }

        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $response = Http::timeout(30)
            ->retry(1, 500)
            ->acceptJson()
            ->get(
                'https://api.data.gov.in/resource/' . $resourceId,
                [
                    'api-key' => $apiKey,
                    'format' => 'json',
                    'offset' => $offset,
                    'limit' => $limit,
                ]
            );

        if (!$response->successful()) {
            throw new RuntimeException(
                'data.gov.in returned HTTP ' . $response->status() . '. Check the resource ID, API key and API access.'
            );
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException('The API response was not valid JSON.');
        }

        if (isset($payload['error'])) {
            throw new RuntimeException('data.gov.in API error: ' . (string) $payload['error']);
        }

        return $payload;
    }

    public function records(array $payload): array
    {
        $records = $payload['records'] ?? [];

        if (!is_array($records)) {
            return [];
        }

        return array_values(array_filter($records, 'is_array'));
    }

    public function columns(array $records): array
    {
        if (empty($records)) {
            return [];
        }

        return array_keys($records[0]);
    }
}