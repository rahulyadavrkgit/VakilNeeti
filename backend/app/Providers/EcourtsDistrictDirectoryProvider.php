<?php

namespace App\Providers;

use App\Contracts\CourtDataProvider;
use App\Data\CourtDataRecord;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use DOMDocument;
use DOMXPath;

class EcourtsDistrictDirectoryProvider implements CourtDataProvider
{
    private const DIRECTORY_URL =
        'https://ecourts.gov.in/ecourts2.0/?p=dist_court';

    public function providerKey(): string
    {
        return 'ecourts_district_services';
    }

    public function providerName(): string
    {
        return 'eCourts District Court Directory';
    }

    public function supports(string $entityType): bool
    {
        return in_array($entityType, [
            'state_directory',
            'district_directory_candidates',
        ], true);
    }

    public function fetch(string $entityType, array $filters = []): iterable
    {
        if (!$this->supports($entityType)) {
            throw new RuntimeException('Unsupported eCourts directory entity.');
        }

        $stateRecords = $this->fetchStateRecords();

        if ($entityType === 'state_directory') {
            return $stateRecords;
        }

        $results = [];

        foreach ($stateRecords as $state) {
            $stateUrl = $state->sourceUrl;

            try {
                $response = $this->request($stateUrl);

                $links = $this->extractLinks($response->body(), $stateUrl);

                foreach ($links as $link) {
                    if (!$this->isCourtDirectoryCandidate($link)) {
                        continue;
                    }

                    $externalId = hash(
                        'sha256',
                        $state->externalId . '|' . $link['url']
                    );

                    $results[$externalId] = new CourtDataRecord(
                        entityType: 'district_directory_candidates',
                        externalId: $externalId,
                        attributes: [
                            'display_name' => $link['name'],
                            'state_slug' => $state->externalId,
                            'state_name' => $state->attributes['display_name'],
                            'candidate_url' => $link['url'],
                            'verification_status' => 'pending_verification',
                        ],
                        sourceUrl: $link['url'],
                        sourceUpdatedAt: null,
                        rawPayload: [
                            'state_directory_url' => $stateUrl,
                            'anchor_text' => $link['name'],
                            'original_href' => $link['href'],
                        ]
                    );
                }
            } catch (\Throwable $exception) {
                // A failed state page is not treated as an empty directory.
                report($exception);
            }
        }

        return array_values($results);
    }

    private function fetchStateRecords(): array
    {
        $response = $this->request(self::DIRECTORY_URL);

        $links = $this->extractLinks(
            $response->body(),
            self::DIRECTORY_URL
        );

        $records = [];

        foreach ($links as $link) {
            if (!preg_match(
                '~(?:\?|&)p=dist_court/([a-z0-9_-]+)~i',
                $link['href'],
                $matches
            )) {
                continue;
            }

            $slug = strtolower($matches[1]);

            if ($slug === '') {
                continue;
            }

            $records[$slug] = new CourtDataRecord(
                entityType: 'state_directory',
                externalId: $slug,
                attributes: [
                    'display_name' => $link['name'],
                    'directory_slug' => $slug,
                    'source_status' => 'pending_verification',
                ],
                sourceUrl: 'https://ecourts.gov.in/ecourts2.0/?p=dist_court/' .
                    rawurlencode($slug),
                sourceUpdatedAt: null,
                rawPayload: [
                    'anchor_text' => $link['name'],
                    'original_href' => $link['href'],
                ]
            );
        }

        if (count($records) === 0) {
            throw new RuntimeException(
                'No State/UT links were discovered on the official directory.'
            );
        }

        return array_values($records);
    }

    private function request(string $url)
    {
        $response = Http::accept('text/html')
            ->timeout(30)
            ->retry(2, 1000)
            ->get($url);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Official directory request failed: HTTP ' .
                $response->status() . ' - ' . $url
            );
        }

        if (stripos($response->body(), '<html') === false) {
            throw new RuntimeException('Unexpected directory response.');
        }

        return $response;
    }

    private function extractLinks(string $html, string $baseUrl): array
    {
        $previousSetting = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument();

            if (!$document->loadHTML($html)) {
                throw new RuntimeException('Unable to parse directory HTML.');
            }

            $xpath = new DOMXPath($document);
            $anchors = $xpath->query('//a[@href]');
            $links = [];

            foreach ($anchors as $anchor) {
                $href = trim($anchor->getAttribute('href'));
                $name = trim(preg_replace(
                    '/\s+/u',
                    ' ',
                    $anchor->textContent
                ));

                if ($href === '' || $name === '') {
                    continue;
                }

                $url = $this->resolveUrl($href, $baseUrl);

                if (!$url) {
                    continue;
                }

                $links[] = [
                    'name' => $name,
                    'href' => $href,
                    'url' => $url,
                ];
            }

            return $links;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);
        }
    }

    private function resolveUrl(string $href, string $baseUrl): ?string
    {
        if (str_starts_with($href, '//')) {
            $url = 'https:' . $href;
        } elseif (preg_match('~^https?://~i', $href)) {
            $url = $href;
        } elseif (str_starts_with($href, '/')) {
            $parts = parse_url($baseUrl);
            $url = ($parts['scheme'] ?? 'https') . '://' .
                ($parts['host'] ?? 'ecourts.gov.in') . $href;
        } elseif (str_starts_with($href, '?')) {
            $url = strtok($baseUrl, '?') . $href;
        } else {
            return null;
        }

        $parts = parse_url($url);

        if (
            !is_array($parts) ||
            !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) ||
            empty($parts['host'])
        ) {
            return null;
        }

        return $url;
    }

    private function isCourtDirectoryCandidate(array $link): bool
    {
        $text = strtolower($link['name']);
        $url = strtolower($link['url']);

        if (
            str_contains($url, 'javascript:') ||
            str_contains($url, 'mailto:') ||
            str_contains($url, 'logout')
        ) {
            return false;
        }

        return
            str_contains($text, 'district court') ||
            str_contains($text, 'district judiciary') ||
            str_contains($text, 'court complex') ||
            str_contains($text, 'subordinate court') ||
            str_contains($url, 'district_court') ||
            str_contains($url, 'district-court') ||
            str_contains($url, 'districtcourt');
    }
}