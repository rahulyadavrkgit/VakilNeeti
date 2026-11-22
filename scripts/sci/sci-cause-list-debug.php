<?php

$url = 'https://www.sci.gov.in/cause-list/';

$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0\r\n",
        'timeout' => 30,
    ],
];

$context = stream_context_create($options);

$html = @file_get_contents($url, false, $context);

if ($html === false) {
    exit("Cause List page download failed.\n");
}

$file = dirname(__DIR__, 2) . '/storage/app/sci-cause-list-debug.html';

if (file_put_contents($file, $html) === false) {
    exit("Could not save HTML file.\n");
}

echo "Cause List HTML downloaded successfully.\n";
echo "File: " . $file . "\n";
echo "Downloaded bytes: " . strlen($html) . "\n";
