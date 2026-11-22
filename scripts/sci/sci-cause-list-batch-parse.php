<?php

$base = dirname(__DIR__, 2) . '/storage/app';

$manifestFile = $base . '/sci-cause-list-extraction-manifest.json';
$outputDirectory = $base . '/sci-cause-list-cases';

if (!file_exists($manifestFile)) {
    exit("Extraction manifest not found.\n");
}

$manifest = json_decode(file_get_contents($manifestFile), true);

if (!is_array($manifest)) {
    exit("Invalid extraction manifest.\n");
}

if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0775, true);
}

$casePattern = '/^\s*(\d+(?:\.\d+)*)\s+((?:Diary|C\.A\.|Crl\.A\.|SLP|W\.P\.|T\.P\.|R\.P\.|M\.A\.|Contempt|Transfer|Review)\b.*?No\..*)$/i';

$processed = 0;
$skipped = 0;
$failed = 0;

foreach ($manifest as $document) {

    if (($document['status'] ?? '') !== 'extracted') {
        continue;
    }

    $textFilename = basename($document['text_file'] ?? '');

    if ($textFilename === '' || $textFilename === '.') {
        $failed++;
        continue;
    }

    $textFile = $base . '/sci-cause-list-text/' . $textFilename;

    if (!file_exists($textFile)) {
        echo "Text file missing: {$textFilename}\n";
        $failed++;
        continue;
    }

    $outputFilename = pathinfo($textFilename, PATHINFO_FILENAME) . '.json';
    $outputFile = $outputDirectory . '/' . $outputFilename;

    if (file_exists($outputFile) && filesize($outputFile) > 0) {
        echo "Already parsed: {$outputFilename}\n";
        $skipped++;
        continue;
    }

    $lines = file($textFile, FILE_IGNORE_NEW_LINES);

    $records = [];
    $current = null;

    foreach ($lines as $index => $line) {

        if (preg_match($casePattern, $line, $matches)) {

            if ($current !== null) {
                $current['raw_text'] = trim(implode("\n", $current['lines']));
                unset($current['lines']);
                $records[] = $current;
            }

            $current = [
                'item_number' => $matches[1],
                'case_reference_line' => trim($matches[2]),
                'source_line' => $index + 1,
                'lines' => [$line]
            ];

            continue;
        }

        if ($current !== null) {
            $current['lines'][] = $line;
        }
    }

    if ($current !== null) {
        $current['raw_text'] = trim(implode("\n", $current['lines']));
        unset($current['lines']);
        $records[] = $current;
    }

    $result = [
        'court' => 'Supreme Court of India',
        'listing_date' => $document['listing_date'] ?? null,
        'category' => $document['category'] ?? null,
        'list_type' => $document['list_type'] ?? null,
        'sub_type' => $document['sub_type'] ?? null,
        'document_url' => $document['document_url'] ?? null,
        'pdf_file' => $document['pdf_file'] ?? null,
        'text_file' => $textFilename,
        'extracted_at' => date('c'),
        'total_records' => count($records),
        'records' => $records
    ];

    $written = file_put_contents(
        $outputFile,
        json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
    );

    if ($written === false) {
        echo "Could not write: {$outputFilename}\n";
        $failed++;
        continue;
    }

    echo "Parsed: {$outputFilename} | Cases: " . count($records) . "\n";
    $processed++;
}

echo "\n================================\n";
echo "BATCH CASE PARSING SUMMARY\n";
echo "================================\n";
echo "New documents parsed: {$processed}\n";
echo "Already parsed: {$skipped}\n";
echo "Failed: {$failed}\n";
echo "Output directory: {$outputDirectory}\n";
