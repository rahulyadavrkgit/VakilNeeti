<?php

$inputFile = __DIR__ . '/storage/app/sci-cause-list-cases-raw.json';
$outputFile = __DIR__ . '/storage/app/sci-cause-list-cases-structured.json';

if (!file_exists($inputFile)) {
    exit("Raw case JSON not found. Run the first-stage parser first.\n");
}

$data = json_decode(file_get_contents($inputFile), true);

if (!is_array($data) || !isset($data['records'])) {
    exit("Invalid raw case JSON.\n");
}

$structured = [];

foreach ($data['records'] as $record) {

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

        if (preg_match('/^(?:\d+\.\d+)\s+Connected\b(.*)$/i', $line, $m)) {
            $connectedMatters[] = trim($m[1]);
        }
    }

    $text = implode("\n", $lines);

    $parties = [
        'petitioner' => null,
        'respondent' => null
    ];

    if (preg_match('/^\s*(.*?)\s+Versus\s*$/mi', $text, $m)) {
        $parties['petitioner'] = trim($m[1]);
    }

    if (preg_match('/^\s*Versus\s*\R\s*(.*)$/mi', $text, $m)) {
        $parties['respondent'] = trim($m[1]);
    }

    $structured[] = [
        'item_number' => $record['item_number'] ?? null,
        'case_reference_line' => $record['case_reference_line'] ?? null,
        'classification' => $classification,
        'petitioner_raw' => $parties['petitioner'],
        'respondent_raw' => $parties['respondent'],
        'advocates' => [],
        'ias' => $ias,
        'connected_matters' => $connectedMatters,
        'listing_notes' => $listingNotes,
        'source_line' => $record['source_line'] ?? null,
        'raw_text' => $raw
    ];
}

$result = [
    'court' => $data['court'] ?? 'Supreme Court of India',
    'source_file' => $data['source_file'] ?? null,
    'extracted_at' => date('c'),
    'total_records' => count($structured),
    'records' => $structured
];

file_put_contents(
    $outputFile,
    json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "Structured extraction completed.\n";
echo "Records processed: " . count($structured) . "\n";
echo "Output: {$outputFile}\n";
