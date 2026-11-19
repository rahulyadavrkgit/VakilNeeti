<?php

$inputDirectory = __DIR__ . '/storage/app/sci-cause-list-structured';
$outputDirectory = __DIR__ . '/storage/app/sci-cause-list-final-cases';

if (!is_dir($inputDirectory)) {
    exit("Structured directory not found.\n");
}

if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0775, true);
}

$files = glob($inputDirectory . '/*.json');

$processed = 0;
$failed = 0;
$totalCandidates = 0;

foreach ($files as $inputFile) {

    $data = json_decode(file_get_contents($inputFile), true);

    if (!is_array($data) || !isset($data['records'])) {
        echo "Invalid JSON: " . basename($inputFile) . "\n";
        $failed++;
        continue;
    }

    foreach ($data['records'] as &$record) {

        $raw = $record['raw_text'] ?? '';
        $advocates = [];

        preg_match_all(
            '/([A-Z][A-Z .\'&()-]{1,90}?)\s*-\s*(\d{1,6})(?=\s|\[|$)/',
            $raw,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {

            $name = trim(preg_replace('/\s+/', ' ', $match[1]));

            if ($name === '') {
                continue;
            }

            $advocates[] = [
                'name_candidate' => $name,
                'aor_number_candidate' => $match[2],
                'verification_status' => 'unverified'
            ];
        }

        $unique = [];

        foreach ($advocates as $advocate) {
            $key = strtoupper($advocate['name_candidate'])
                . '-' . $advocate['aor_number_candidate'];

            $unique[$key] = $advocate;
        }

        $record['advocates'] = array_values($unique);
        $totalCandidates += count($record['advocates']);
    }

    unset($record);

    $data['advocate_extraction_at'] = date('c');

    $outputFile = $outputDirectory . '/' . basename($inputFile);

    $written = file_put_contents(
        $outputFile,
        json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
    );

    if ($written === false) {
        echo "Could not write: " . basename($inputFile) . "\n";
        $failed++;
        continue;
    }

    echo "Processed: " . basename($inputFile) . "\n";
    $processed++;
}

echo "\n================================\n";
echo "ADVOCATE EXTRACTION SUMMARY\n";
echo "================================\n";
echo "Documents processed: {$processed}\n";
echo "Failed: {$failed}\n";
echo "AOR candidates: {$totalCandidates}\n";
echo "Output: {$outputDirectory}\n";
