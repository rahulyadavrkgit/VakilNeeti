<?php

$base = __DIR__;

$scripts = [
    'sci-cause-list-extract.php',
    'sci-cause-list-case-parse.php',
    'sci-cause-list-structure-fields.php',
    'sci-cause-list-advocates.php'
];

echo "====================================\n";
echo "SUPREME COURT CAUSE LIST PIPELINE\n";
echo "====================================\n\n";

foreach ($scripts as $script) {

    $path = $base . '/' . $script;

    if (!file_exists($path)) {
        exit("ERROR: Script not found: {$script}\n");
    }

    echo "Running: {$script}\n";

    passthru(
        escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($path),
        $exitCode
    );

    if ($exitCode !== 0) {
        exit("\nPipeline stopped. Failed at: {$script}\n");
    }

    echo "Completed: {$script}\n\n";
}

echo "====================================\n";
echo "ALL EXTRACTION STAGES COMPLETED\n";
echo "====================================\n";
