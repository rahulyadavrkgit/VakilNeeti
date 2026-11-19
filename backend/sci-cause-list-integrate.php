<?php

$base = __DIR__ . '/storage/app';

$documentsFile = $base . '/sci-cause-list-parsed.json';
$casesFile = $base . '/sci-cause-list-cases-with-advocates.json';
$outputFile = $base . '/sci-cause-list-final.json';

foreach ([$documentsFile, $casesFile] as $file) {
    if (!file_exists($file)) {
        exit("Required file not found: {$file}\n");
    }
}

$documentsData = json_decode(file_get_contents($documentsFile), true);
$casesData = json_decode(file_get_contents($casesFile), true);

if (!isset($documentsData['documents']) || !isset($casesData['records'])) {
    exit("Invalid input JSON structure.\n");
}

/*
 * This sample PDF belongs to this specific document.
 * Do not attach its cases to other PDFs for the same date.
 */

$samplePdfUrl = 'https://api.sci.gov.in/jonew/cl/2026-11-16/M_J_1.pdf';

$matchedDocument = null;

foreach ($documentsData['documents'] as $document) {
    if (($document['document_url'] ?? '') === $samplePdfUrl) {
        $matchedDocument = $document;
        break;
    }
}

if ($matchedDocument === null) {
    exit("Sample PDF not found in cause-list metadata.\n");
}

$result = [
    'court' => 'Supreme Court of India',
    'source_page' => $documentsData['source_page'] ?? null,
    'listing_date' => $matchedDocument['listing_date'],
    'court_scope' => $matchedDocument['court_scope'],
    'category' => $matchedDocument['category'],
    'list_type' => $matchedDocument['list_type'],
    'sub_type' => $matchedDocument['sub_type'],
    'document_label' => $matchedDocument['document_label'],
    'document_url' => $matchedDocument['document_url'],
    'extracted_at' => date('c'),
    'total_cases' => count($casesData['records']),
    'cases' => $casesData['records']
];

file_put_contents(
    $outputFile,
    json_encode(
        $result,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    )
);

echo "Metadata integration completed.\n";
echo "Listing date: {$result['listing_date']}\n";
echo "Document category: {$result['category']}\n";
echo "Document URL: {$result['document_url']}\n";
echo "Case records: {$result['total_cases']}\n";
echo "Output: {$outputFile}\n";
