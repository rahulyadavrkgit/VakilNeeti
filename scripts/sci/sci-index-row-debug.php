<?php

$htmlFile = dirname(__DIR__, 2) . '/storage/app/sci-index-debug.html';
$html = file_get_contents($htmlFile);

libxml_use_internal_errors(true);

$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

$xpath = new DOMXPath($dom);

$rows = $xpath->query('//tr');

$count = 0;

foreach ($rows as $row) {
    if (stripos($row->textContent, 'CHIEF JUSTICE') === false) {
        continue;
    }

    echo "\n--- COURT ROW ---\n";

    $cells = $xpath->query('./td', $row);

    echo "Number of TD cells: " . $cells->length . "\n";

    foreach ($cells as $index => $cell) {
        $text = trim(preg_replace('/\s+/u', ' ', $cell->textContent));

        echo "\nCell " . ($index + 1) . ": " . $text . "\n";
        echo "HTML: " . $dom->saveHTML($cell) . "\n";
    }

    $count++;

    if ($count >= 2) {
        break;
    }
}
