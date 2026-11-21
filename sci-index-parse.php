<?php

$htmlFile = __DIR__ . '/storage/app/sci-index-debug.html';

if (!file_exists($htmlFile)) {
    exit("HTML file not found.\n");
}

$html = file_get_contents($htmlFile);

if (!class_exists(DOMDocument::class)) {
    exit("PHP DOM extension is not enabled.\n");
}

libxml_use_internal_errors(true);

$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

$xpath = new DOMXPath($dom);

$records = [];
$currentDate = null;
$indexCount = 0;

$rows = $xpath->query('//tr');

foreach ($rows as $row) {

    $cells = [];

    foreach ($xpath->query('./td', $row) as $cell) {
        $cells[] = trim(preg_replace(
            '/\s+/u',
            ' ',
            $cell->textContent
        ));
    }

    if (count($cells) === 0) {
        continue;
    }

    // Identify the index date.
    if (preg_match('/Index for\s+(\d{2}-\d{2}-\d{4})/i', $cells[0], $matches)) {

        $date = DateTime::createFromFormat('d-m-Y', $matches[1]);

        $currentDate = $date
            ? $date->format('Y-m-d')
            : null;

        if ($currentDate) {
            $indexCount++;
        }

        // Extract PDF links belonging to this index.
        foreach ($xpath->query('.//a[@href]', $row) as $link) {

            $url = $link->getAttribute('href');
            $filename = basename(parse_url($url, PHP_URL_PATH));

            $type = 'other';

            if (str_starts_with($filename, 'M_J_')) {
                $type = 'judge_miscellaneous';
            } elseif (str_starts_with($filename, 'F_J_')) {
                $type = 'judge_regular';
            } elseif (str_starts_with($filename, 'M_C_')) {
                $type = 'chamber';
            } elseif (str_starts_with($filename, 'M_R_')) {
                $type = 'registrar';
            }

            $records['indexes'][$currentDate]['pdfs'][] = [
                'type' => $type,
                'label' => trim($link->textContent),
                'url' => $url,
            ];
        }

        continue;
    }

    // Ignore rows before the first index date.
    if (!$currentDate || count($cells) < 3) {
        continue;
    }

    // Ignore table headings.
    if (!ctype_digit($cells[0])) {
        continue;
    }

    // Preserve every court allocation row.
    $records['indexes'][$currentDate]['courts'][] = [
        'court_number' => $cells[0],
        'judges' => $cells[1],
        'session' => $cells[2],
    ];
}

$output = __DIR__ . '/storage/app/sci-index-parsed.json';

file_put_contents(
    $output,
    json_encode(
        $records,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    )
);

echo "Parsing completed.\n";
echo "Index dates found: " . $indexCount . "\n";
echo "Output: " . $output . "\n";

foreach ($records['indexes'] ?? [] as $date => $index) {
    echo "\nDate: " . $date . "\n";
    echo "Court rows: " . count($index['courts'] ?? []) . "\n";
    echo "PDF links: " . count($index['pdfs'] ?? []) . "\n";
}
