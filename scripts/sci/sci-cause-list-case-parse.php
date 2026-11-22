<?php

$inputFile = dirname(__DIR__, 2) . '/storage/app/sci-sample-cause-list.txt';
$outputFile = dirname(__DIR__, 2) . '/storage/app/sci-cause-list-cases-raw.json';

if (!file_exists($inputFile)) {
    exit("Cause list text file not found.\n");
}

$lines = file($inputFile, FILE_IGNORE_NEW_LINES);

$casePattern = '/^\s*(\d+(?:\.\d+)*)\s+((?:Diary|C\.A\.|Crl\.A\.|SLP|W\.P\.|T\.P\.|R\.P\.|M\.A\.|Contempt|Transfer|Review)\b.*?No\..*)$/i';

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
    'source_file' => basename($inputFile),
    'extracted_at' => date('c'),
    'total_records' => count($records),
    'records' => $records
];

file_put_contents(
    $outputFile,
    json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "Raw case extraction completed.\n";
echo "Records extracted: " . count($records) . "\n";
echo "Output: {$outputFile}\n";
