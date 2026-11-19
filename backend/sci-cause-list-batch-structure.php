<?php

$inputDirectory = __DIR__ . '/storage/app/sci-cause-list-cases';
$outputDirectory = __DIR__ . '/storage/app/sci-cause-list-structured';

if (!is_dir($inputDirectory)) {
    exit("Case JSON directory not found.\n");
}

if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0775, true);
}

$files = glob($inputDirectory . '/*.json');

$processed = 0;
$failed = 0;

foreach ($files as $inputFile) {

    $data = json_decode(file_get_contents($inputFile), true);

    if (!is_array($data) || !isset($data['records'])) {
        echo "Invalid JSON: " . basename($inputFile) . "\n";
        $failed++;
        continue;
    }

    foreach ($data['records'] as &$record) {

        $raw = $record['raw_text'] ?? '';
        $lines = preg_split('/\R/', $raw);

        $classification = null;
        $ias = [];
        $listingNotes = [];
        $connectedMatters = [];

        foreach ($lines as $line) {

            $line = trim($line);

            if (preg_match('/^([IVX]+(?:-[A-Z]+)?|PIL-[A-Z]+)$/', $line)) {
                $classification = $line;
            }

            if (preg_match('/^IA No\.?\s*([0-9\/-]+)\s*[-–:]?\s*(.*)$/i', $line, $m)) {
                $ias[] = [
                    'ia_number' => trim($m[1]),
                    'description' => trim($m[2])
                ];
            }

            if (preg_match('/^\{(.+)\}$/', $line, $m)) {
                $listingNotes[] = trim($m[1]);
            }

            if (preg_match('/^(\d+\.\d+)\s+Connected\b(.*)$/i', $line, $m)) {
                $connectedMatters[] = [
                    'item_number' => $m[1],
                    'description' => trim($m[2])
                ];
            }
        }

        $record['classification'] = $classification;
        $record['petitioner_raw'] = null;
        $record['respondent_raw'] = null;
        $record['advocates'] = [];
        $record['ias'] = $ias;
        $record['connected_matters'] = $connectedMatters;
        $record['listing_notes'] = $listingNotes;
    }

    unset($record);

    $data['structured_at'] = date('c');

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

    echo "Structured: " . basename($inputFile) .
        " | Cases: " . count($data['records']) . "\n";

    $processed++;
}

echo "\n================================\n";
echo "BATCH STRUCTURING SUMMARY\n";
echo "================================\n";
echo "Documents processed: {$processed}\n";
echo "Failed: {$failed}\n";
echo "Output directory: {$outputDirectory}\n";
