<?php

$htmlFile = __DIR__ . '/storage/app/sci-cause-list-debug.html';
$outputFile = __DIR__ . '/storage/app/sci-cause-list-parsed.json';

if (!file_exists($htmlFile)) {
    exit("Cause List HTML file not found.\n");
}

if (!class_exists(DOMDocument::class)) {
    exit("PHP DOM extension is not enabled.\n");
}

$html = file_get_contents($htmlFile);

libxml_use_internal_errors(true);

$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

$xpath = new DOMXPath($dom);

// Locate the actual Cause Lists at a Glance table.
$table = $xpath->query(
    '//table[contains(concat(" ", normalize-space(@class), " "), " causelist-glance ")]'
)->item(0);

if (!$table) {
    exit("Cause Lists at a Glance table not found.\n");
}

$categories = [
    ['category' => 'judge', 'list_type' => 'advance', 'sub_type' => 'advance'],
    ['category' => 'judge', 'list_type' => 'miscellaneous', 'sub_type' => 'main'],
    ['category' => 'judge', 'list_type' => 'miscellaneous', 'sub_type' => 'supplementary'],
    ['category' => 'judge', 'list_type' => 'regular', 'sub_type' => 'main'],
    ['category' => 'judge', 'list_type' => 'regular', 'sub_type' => 'supplementary'],
    ['category' => 'chamber', 'list_type' => 'chamber', 'sub_type' => 'main'],
    ['category' => 'chamber', 'list_type' => 'chamber', 'sub_type' => 'supplementary'],
    ['category' => 'single_judge', 'list_type' => 'single_judge', 'sub_type' => 'main'],
    ['category' => 'single_judge', 'list_type' => 'single_judge', 'sub_type' => 'supplementary'],
    ['category' => 'review_curative', 'list_type' => 'review_curative', 'sub_type' => 'main'],
    ['category' => 'review_curative', 'list_type' => 'review_curative', 'sub_type' => 'supplementary'],
    ['category' => 'registrar', 'list_type' => 'registrar', 'sub_type' => 'main'],
    ['category' => 'registrar', 'list_type' => 'registrar', 'sub_type' => 'supplementary'],
    ['category' => 'weekly', 'list_type' => 'weekly', 'sub_type' => 'weekly'],
];

$records = [
    'court' => 'Supreme Court of India',
    'source_page' => 'https://www.sci.gov.in/cause-list/',
    'extracted_at' => date('c'),
    'documents' => [],
];

$minimumDate = new DateTimeImmutable('2026-11-16');

$rows = $xpath->query('./tbody/tr', $table);

$currentCourtScope = null;
$processedRows = 0;
$skippedRows = 0;

foreach ($rows as $row) {

    $cells = $xpath->query('./td', $row);

    // First row contains the court scope because of rowspan.
    if ($cells->length === 15) {
        $currentCourtScope = trim($cells->item(0)->textContent);
        $documentStart = 1;
    } elseif ($cells->length === 14) {
        // Subsequent rows inherit the court scope.
        $documentStart = 0;
    } else {
        $skippedRows++;
        continue;
    }

    if (!$currentCourtScope) {
        $skippedRows++;
        continue;
    }

    $processedRows++;

    foreach ($categories as $index => $category) {

        $cell = $cells->item($documentStart + $index);

        if (!$cell) {
            continue;
        }

        $link = $xpath->query('.//a[@href]', $cell)->item(0);

        // Do not generate URLs for empty cells.
        if (!$link) {
            continue;
        }

        $url = trim($link->getAttribute('href'));
        $label = trim($link->textContent);

        if (!$url) {
            continue;
        }

        // Extract the date from the link text.
        $date = DateTimeImmutable::createFromFormat('!d-m-Y', $label);

        if (!$date) {
            continue;
        }

        // Only retain listing dates from 16 November 2026 onward.
        if ($date < $minimumDate) {
            continue;
        }

        $records['documents'][] = [
            'listing_date' => $date->format('Y-m-d'),
            'court_scope' => $currentCourtScope,
            'category' => $category['category'],
            'list_type' => $category['list_type'],
            'sub_type' => $category['sub_type'],
            'document_label' => $label,
            'document_url' => $url,
        ];
    }
}

// Remove duplicate records by actual document URL and metadata.
$uniqueDocuments = [];

foreach ($records['documents'] as $document) {
    $key = implode('|', [
        $document['listing_date'],
        $document['court_scope'],
        $document['document_url'],
    ]);

    $uniqueDocuments[$key] = $document;
}

$records['documents'] = array_values($uniqueDocuments);

$json = json_encode(
    $records,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);

if ($json === false) {
    exit("JSON encoding failed: " . json_last_error_msg() . "\n");
}

if (file_put_contents($outputFile, $json) === false) {
    exit("Could not write parsed JSON file.\n");
}

echo "Cause List parsing completed successfully.\n";
echo "Court scope: " . $currentCourtScope . "\n";
echo "Rows processed: " . $processedRows . "\n";
echo "Rows skipped: " . $skippedRows . "\n";
echo "Documents extracted: " . count($records['documents']) . "\n";
echo "Output: " . $outputFile . "\n";

$summary = [];

foreach ($records['documents'] as $document) {
    $date = $document['listing_date'];

    if (!isset($summary[$date])) {
        $summary[$date] = 0;
    }

    $summary[$date]++;
}

foreach ($summary as $date => $count) {
    echo "\nDate: " . $date . "\n";
    echo "Documents: " . $count . "\n";
}
