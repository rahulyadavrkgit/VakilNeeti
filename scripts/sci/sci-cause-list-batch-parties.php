<?php

$inputDirectory = dirname(__DIR__, 2) . '/storage/app/sci-cause-list-final-cases';
$outputDirectory = dirname(__DIR__, 2) . '/storage/app/sci-cause-list-party-data';

if (!is_dir($inputDirectory)) {
    exit("Final case directory not found.\n");
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

        $lines = preg_split('/\R/', $record['raw_text'] ?? '');

        $versusIndex = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*Versus\s*$/i', trim($line))) {
                $versusIndex = $index;
                break;
            }
        }

        $petitionerLine = null;
        $respondentLine = null;

        if ($versusIndex !== null) {

            // Find the nearest non-empty line before Versus.
            for ($i = $versusIndex - 1; $i >= 0; $i--) {

                $candidate = trim($lines[$i]);

                if ($candidate === '') {
                    continue;
                }

                if (
                    preg_match('/^IA No\./i', $candidate) ||
                    preg_match('/^\{.*\}$/', $candidate) ||
                    preg_match('/^(PART-|MISCELLANEOUS|REGULAR|CHAMBER)/i', $candidate) ||
                    preg_match('/^(DAILY CAUSE LIST|SUPREME COURT OF INDIA|HON.BLE)/i', $candidate)
                ) {
                    continue;
                }

                if (preg_match('/^[IVX]+(?:-[A-Z]+)?$/', $candidate)) {
                    continue;
                }

                $petitionerLine = $candidate;
                break;
            }

            // Find the nearest non-empty line after Versus.
            for ($i = $versusIndex + 1; $i < count($lines); $i++) {

                $candidate = trim($lines[$i]);

                if ($candidate === '') {
                    continue;
                }

                if (
                    preg_match('/^IA No\./i', $candidate) ||
                    preg_match('/^\{.*\}$/', $candidate)
                ) {
                    continue;
                }

                $respondentLine = $candidate;
                break;
            }
        }

        $record['parties'] = [
            'petitioner_line_raw' => $petitionerLine,
            'respondent_line_raw' => $respondentLine,
            'extraction_status' => $versusIndex !== null
                ? 'party_lines_detected'
                : 'versus_separator_not_found',
            'verification_status' => 'unverified'
        ];
    }

    unset($record);

    $data['party_extraction_at'] = date('c');

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

    echo "Party extraction processed: " . basename($inputFile) . "\n";
    $processed++;
}

echo "\n================================\n";
echo "PARTY EXTRACTION SUMMARY\n";
echo "================================\n";
echo "Documents processed: {$processed}\n";
echo "Failed: {$failed}\n";
echo "Output directory: {$outputDirectory}\n";
