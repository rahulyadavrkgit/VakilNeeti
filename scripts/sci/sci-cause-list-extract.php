<?php

$pdfFile = dirname(__DIR__, 2) . '/storage/app/sci-sample-cause-list.pdf';
$textFile = dirname(__DIR__, 2) . '/storage/app/sci-sample-cause-list.txt';

if (!file_exists($pdfFile)) {
    exit("PDF file not found.\n");
}

$command = 'pdftotext -layout '
    . escapeshellarg($pdfFile) . ' '
    . escapeshellarg($textFile);

exec($command, $output, $exitCode);

if ($exitCode !== 0) {
    exit("PDF text extraction failed. Exit code: {$exitCode}\n");
}

if (!file_exists($textFile)) {
    exit("Extracted text file was not created.\n");
}

$lineCount = count(file($textFile));

echo "PDF extraction successful.\n";
echo "Input PDF: {$pdfFile}\n";
echo "Output text: {$textFile}\n";
echo "Extracted lines: {$lineCount}\n";
