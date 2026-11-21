<?php

namespace App\Services\CourtData;

use App\Contracts\CourtDataProvider;
use App\Data\CourtDataRecord;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use DOMDocument;
use DOMXPath;

class SupremeCourtWebsiteProvider implements CourtDataProvider
{
    private const SOURCE_URL = 'https://www.sci.gov.in/index/';

    public function providerKey(): string
    {
        return 'supreme_court_website';
    }

    public function providerName(): string
    {
        return 'Supreme Court of India Official Website';
    }

    public function supports(string $entityType): bool
    {
        return $entityType === 'cause_list_index';
    }

    /**
     * Retrieve published Supreme Court cause-list index rows.
     *
     * This provider does not access CAPTCHA-protected case searches
     * and does not claim that published index data is live case status.
     *
     * @return iterable<CourtDataRecord>
     */
    public function fetch(
        string $entityType,
        array $filters = []
    ): iterable {
        if (!$this->supports($entityType)) {
            throw new InvalidArgumentException(
                "Unsupported Supreme Court entity type: {$entityType}"
            );
        }

        $requestedDate = null;

        if (!empty($filters['date'])) {
            $requestedDate = $this->normaliseDate(
                (string) $filters['date']
            );
        }

        $response = Http::accept('text/html')
            ->withUserAgent('VakilNeeti Court Data Connector/1.0')
            ->timeout((int) config('court-data.sync.timeout', 30))
            ->retry(
                (int) config('court-data.sync.retry_attempts', 2),
                (int) config('court-data.sync.retry_delay_seconds', 1) * 1000
            )
            ->get(self::SOURCE_URL);

        if (!$response->successful()) {
            throw new RuntimeException(
                'The Supreme Court index page returned HTTP status '
                . $response->status()
            );
        }

        $html = $response->body();

        if (
            trim($html) === ''
            || stripos($html, 'Court Number') === false
            || stripos($html, 'Index') === false
        ) {
            throw new RuntimeException(
                'The Supreme Court page did not contain the expected index content.'
            );
        }

        $previousLibxmlState = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument('1.0', 'UTF-8');

            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8">' . $html,
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );

            if (!$loaded) {
                throw new RuntimeException(
                    'The Supreme Court index HTML could not be parsed.'
                );
            }

            $xpath = new DOMXPath($document);
            $tables = $xpath->query('//table');

            if (!$tables || $tables->length === 0) {
                throw new RuntimeException(
                    'No tables were found on the Supreme Court index page.'
                );
            }

            $records = [];
            $recognisedTables = 0;

            foreach ($tables as $table) {
                $rows = $xpath->query('.//tr', $table);

                if (!$rows || $rows->length === 0) {
                    continue;
                }

                $headerRowIndex = null;

                foreach ($rows as $rowIndex => $row) {
                    $cells = $xpath->query('./th|./td', $row);

                    if (!$cells || $cells->length < 3) {
                        continue;
                    }

                    $first = strtolower(
                        $this->cleanText($cells->item(0)->textContent)
                    );

                    $second = strtolower(
                        $this->cleanText($cells->item(1)->textContent)
                    );

                    $third = strtolower(
                        $this->cleanText($cells->item(2)->textContent)
                    );

                    if (
                        str_contains($first, 'court number')
                        && str_contains($second, 'judges')
                        && str_contains($third, 'session')
                    ) {
                        $headerRowIndex = $rowIndex;
                        break;
                    }
                }

                if ($headerRowIndex === null) {
                    continue;
                }

                $recognisedTables++;

                /*
                 * The date heading may be stored as a text node
                 * rather than a standalone HTML element.
                 *
                 * Search the nearest preceding text node containing
                 * "Index for".
                 */
                $headingQuery = $xpath->query(
                    "preceding::text()[
                        contains(
                            translate(
                                normalize-space(.),
                                'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                'abcdefghijklmnopqrstuvwxyz'
                            ),
                            'index for'
                        )
                    ][1]",
                    $table
                );

                $headingText = $headingQuery && $headingQuery->length > 0
                    ? $this->cleanText(
                        $headingQuery->item(0)->textContent
                    )
                    : '';

                if (
                    !preg_match(
                        '/index\s+for\s+(\d{2}-\d{2}-\d{4})/i',
                        $headingText,
                        $dateMatches
                    )
                ) {
                    throw new RuntimeException(
                        'A published index table was found, but its listing date could not be identified. '
                        . 'Extracted text node: '
                        . ($headingText !== '' ? $headingText : '[empty]')
                    );
                }

                $listingDate = $this->normaliseDate($dateMatches[1]);

                if (
                    $requestedDate !== null
                    && $listingDate !== $requestedDate
                ) {
                    continue;
                }

                foreach ($rows as $rowIndex => $row) {
                    if ($rowIndex <= $headerRowIndex) {
                        continue;
                    }

                    $cells = $xpath->query('./th|./td', $row);

                    if (!$cells || $cells->length < 2) {
                        continue;
                    }

                    $courtNumber = $this->cleanText(
                        $cells->item(0)->textContent
                    );

                    $judges = $this->cleanText(
                        $cells->item(1)->textContent
                    );

                    $session = $cells->length >= 3
                        ? $this->cleanText($cells->item(2)->textContent)
                        : '';

                    if ($courtNumber === '' || $judges === '') {
                        continue;
                    }

                    $externalId = sprintf(
                        'index:%s:court:%s:row:%d',
                        $listingDate,
                        $courtNumber,
                        $rowIndex
                    );

                    $records[] = new CourtDataRecord(
                        entityType: 'cause_list_index',
                        externalId: $externalId,
                        attributes: [
                            'parent_external_id' => 'index:' . $listingDate,
                            'court_name' => 'Supreme Court of India',
                            'listing_date' => $listingDate,
                            'court_number' => $courtNumber,
                            'judges' => $judges,
                            'session' => $session !== '' ? $session : null,
                            'record_category' => 'published_cause_list_index',
                            'data_classification' => 'published_listing_information',
                            'is_live_data' => false,
                            'source_page' => self::SOURCE_URL,
                        ],
                        sourceUrl: self::SOURCE_URL,
                        sourceUpdatedAt: null,
                        rawPayload: [
                            'listing_date' => $listingDate,
                            'court_number' => $courtNumber,
                            'judges' => $judges,
                            'session' => $session,
                        ]
                    );
                }
            }

            if ($recognisedTables === 0) {
                throw new RuntimeException(
                    'The page structure changed: no recognised cause-list index table was found.'
                );
            }

            return $records;

        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlState);
        }
    }

    private function cleanText(string $value): string
    {
        $value = html_entity_decode(
            $value,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        return trim(
            preg_replace('/\s+/u', ' ', $value) ?? ''
        );
    }

    private function normaliseDate(string $value): string
    {
        $value = trim($value);

        $formats = [
            '!Y-m-d',
            '!d-m-Y',
            '!d/m/Y',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat(
                $format,
                $value
            );

            $errors = DateTimeImmutable::getLastErrors();

            if (
                $date !== false
                && (
                    $errors === false
                    || (
                        $errors['warning_count'] === 0
                        && $errors['error_count'] === 0
                    )
                )
            ) {
                return $date->format('Y-m-d');
            }
        }

        throw new InvalidArgumentException(
            'Invalid date filter. Use YYYY-MM-DD or DD-MM-YYYY.'
        );
    }
}
