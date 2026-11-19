<?php

$projectRoot = __DIR__;
$autoload = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$phpunit = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'phpunit' . DIRECTORY_SEPARATOR . 'phpunit' . DIRECTORY_SEPARATOR . 'phpunit';
$args = $_SERVER['argv'] ?? [];
array_shift($args);

if (!is_file($autoload)) {
    fwrite(STDERR, "Missing autoloader: {$autoload}\n");
    exit(1);
}

if (!is_file($phpunit)) {
    fwrite(STDERR, "Missing PHPUnit runner: {$phpunit}\n");
    exit(1);
}

$command = array_merge(['php', '-d', 'auto_prepend_file=' . $autoload, $phpunit, '--no-configuration'], $args);
$escaped = [];
foreach ($command as $part) {
    $escaped[] = preg_match('/\s/', $part) ? '"' . str_replace('"', '\\"', $part) . '"' : $part;
}
$commandString = implode(' ', $escaped);

passthru($commandString, $exitCode);
exit($exitCode);
