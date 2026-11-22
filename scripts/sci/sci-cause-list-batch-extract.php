<?php

$base = dirname(__DIR__, 2) . '/storage/app';

$manifestFile = $base . '/sci-cause-list-download-manifest.json';
$downloadDirectory = $base . '/sci-cause-lists';
$outputDirectory = $base . '/sci-cause-list-text';
$extractionManifestFile = $base . '/sci-cause-list-extraction-manifest.json';

if (!file_exists($manifestFile)) {
    exit("Download manifest not found.\n");
}

$manifest = json_decode(file_get_contents($manifestFile), true);

if (!is_array($manifest)) {
    exit("Invalid download manifest.\n");
}

if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0775, true);
}

$results = [];

if (file_exists($extractionManifestFile)) {
    $results = json_decode(
        file_get_contents($extractionManifestFile),
        true
    ) ?? [];
}

if (!is_array($results)) {
    $results = [];
}

$processed = 0;
$skipped = 0;
$failed = 0;

foreach ($manifest as $document) {

    $filename = basename($document['local_file'] ?? '');

    if ($filename === '' || $filename === '.') {
        $failed++;
        continue;
    }

    $pdfFile = $downloadDirectory . '/' . $filename;

    $textFilename = pathinfo($filename, PATHINFO_FILENAME) . '.txt';
    $textFile = $outputDirectory . '/' . $textFilename;

    if (!file_exists($pdfFile) || filesize($pdfFile) === 0) {
        echo "PDF missing or empty: {$filename}\n";
        $failed++;
        continue;
    }

    $handle = fopen($pdfFile, 'rb');
    $signature = $handle ? fread($handle, 5) : '';
    if ($handle) {
        fclose($handle);
    }

    if ($signature !== '%PDF-') {
        echo "Invalid PDF signature: {$filename}\n";
        $failed++;
        continue;
    }

    if (file_exists($textFile) && filesize($textFile) > 0) {
        echo "Already extracted: {$textFilename}\n";
        $skipped++;
        continue;
    }

    $command = 'pdftotext -layout '
        . escapeshellarg($pdfFile) . ' '
        . escapeshellarg($textFile);

    exec($command . ' 2>&1', $commandOutput, $exitCode);

    if (
        $exitCode !== 0 ||
        !file_exists($textFile) ||
        filesize($textFile) === 0
    ) {
        echo "Extraction failed: {$filename}\n";

        if (!empty($commandOutput)) {
            echo implode("\n", $commandOutput) . "\n";
        }

        $failed++;
        continue;
    }

    $entry = [
        'listing_date' => $document['listing_date'] ?? null,
        'category' => $document['category'] ?? null,
        'list_type' => $document['list_type'] ?? null,
        'sub_type' => $document['sub_type'] ?? null,
        'document_url' => $document['document_url'] ?? null,
        'pdf_file' => $filename,
        'text_file' => $textFilename,
        'text_size' => filesize($textFile),
        'extracted_at' => date('c'),
        'status' => 'extracted'
    ];

    $results = array_values(array_filter(
        $results,
        fn($item) => ($item['pdf_file'] ?? '') !== $filename
    ));

    $results[] = $entry;

    file_put_contents(
        $extractionManifestFile,
        json_encode(
            $results,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
    );

    echo "Extracted: {$textFilename}\n";
    $processed++;
}

echo "\n================================\n";
echo "BATCH EXTRACTION SUMMARY\n";
echo "================================\n";
echo "Newly extracted: {$processed}\n";
echo "Already extracted: {$skipped}\n";
echo "Failed: {$failed}\n";
echo "Output directory: {$outputDirectory}\n";
echo "Extraction manifest: {$extractionManifestFile}\n";
