<?php

$base = __DIR__;

$scripts = [
    'sci-cause-list-download.php',
    'sci-cause-list-batch-extract.php',
    'sci-cause-list-batch-parse.php',
    'sci-cause-list-batch-structure.php',
    'sci-cause-list-batch-advocates.php',
    'sci-cause-list-batch-parties.php'
];

echo "\n";
echo "============================================\n";
echo " VAKILNEETI - SUPREME COURT DATA PIPELINE\n";
echo "============================================\n\n";

$startTime = microtime(true);

foreach ($scripts as $index => $script) {

    $path = $base . DIRECTORY_SEPARATOR . $script;

    if (!file_exists($path)) {
        exit("ERROR: Missing script: {$script}\n");
    }

    echo "\n";
    echo "STAGE " . ($index + 1) . ": {$script}\n";
    echo "--------------------------------------------\n";

    $command = escapeshellarg(PHP_BINARY)
        . ' '
        . escapeshellarg($path);

    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        exit("\nPIPELINE STOPPED.\nFailed stage: {$script}\n");
    }

    echo "\nStage completed successfully.\n";
}

$duration = round(microtime(true) - $startTime, 2);

echo "\n";
echo "============================================\n";
echo " PIPELINE EXECUTION FINISHED\n";
echo "============================================\n";
echo "Execution time: {$duration} seconds\n";
echo "Final output directory:\n";
echo "storage/app/sci-cause-list-party-data/\n";

