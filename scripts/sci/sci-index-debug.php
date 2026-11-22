<?php

$url = 'https://www.sci.gov.in/index/';

$html = file_get_contents($url);

if ($html === false) {
    exit("Download failed\n");
}

$file = dirname(__DIR__, 2) . '/storage/app/sci-index-debug.html';

file_put_contents($file, $html);

echo "HTML downloaded successfully.\n";
echo "File: " . $file . "\n";
echo "Downloaded bytes: " . strlen($html) . "\n";
