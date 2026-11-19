<?php

$inputFile = __DIR__ . '/storage/app/sci-cause-list-cases-structured.json';
$outputFile = __DIR__ . '/storage/app/sci-cause-list-cases-with-advocates.json';

if (!file_exists($inputFile)) {
    exit("Structured case JSON not found.\n");
}

$data = json_decode(file_get_contents($inputFile), true);

if (!is_array($data) || !isset($data['records'])) {
    exit("Invalid structured case JSON.\n");
}

foreach ($data['records'] as &$record) {

    $raw = $record['raw_text'] ?? '';
    $advocates = [];

    /*
     * Extract name-number patterns, for example:
     * VISHESH VIJAY KALRA-4021
     * E.C. AGRAWALA-177
     * ANAND MISHRA-2252
     */

    preg_match_all(
        '/([A-Z][A-Z .\'&()-]{1,90}?)\s*-\s*(\d{1,6})(?=\s|\[|$)/',
        $raw,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {

        $name = trim($match[1]);

        $name = preg_replace('/\s+/', ' ', $name);

        if ($name === '') {
            continue;
        }

        $advocates[] = [
            'name' => $name,
            'aor_number' => $match[2]
        ];
    }

    $unique = [];

    foreach ($advocates as $advocate) {
        $key = strtoupper($advocate['name']) . '-' . $advocate['aor_number'];
        $unique[$key] = $advocate;
    }

    $record['advocates'] = array_values($unique);
}

unset($record);

$data['extracted_at'] = date('c');

file_put_contents(
    $outputFile,
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "Advocate extraction completed.\n";
echo "Records processed: " . count($data['records']) . "\n";
echo "Output: {$outputFile}\n";
