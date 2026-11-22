<?php

$base = dirname(__DIR__, 2) . '/storage/app';

$inputFile = $base . '/sci-cause-list-parsed.json';
$downloadDirectory = $base . '/sci-cause-lists';
$manifestFile = $base . '/sci-cause-list-download-manifest.json';

if (!file_exists($inputFile)) {
    exit("Cause list metadata file not found.\n");
}

$data = json_decode(file_get_contents($inputFile), true);

if (!isset($data['documents'])) {
    exit("Invalid cause list metadata.\n");
}

if (!is_dir($downloadDirectory)) {
    mkdir($downloadDirectory, 0775, true);
}

$manifest = [];

if (file_exists($manifestFile)) {
    $manifest = json_decode(file_get_contents($manifestFile), true) ?? [];
}

$downloaded = 0;
$skipped = 0;
$failed = 0;

foreach ($data['documents'] as $index => $document) {

    $url = $document['document_url'] ?? '';

    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        echo "Skipping invalid URL.\n";
        $failed++;
        continue;
    }

    $date = $document['listing_date'] ?? 'unknown-date';
    $category = $document['category'] ?? 'unknown';
    $type = $document['list_type'] ?? 'unknown';
    $subType = $document['sub_type'] ?? 'unknown';

    $filename = sprintf(
        '%s_%s_%s_%s_%03d.pdf',
        $date,
        $category,
        $type,
        $subType,
        $index + 1
    );

    $filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename);

    $destination = $downloadDirectory . '/' . $filename;

    if (file_exists($destination) && filesize($destination) > 0) {
        echo "Already downloaded: {$filename}\n";
        $skipped++;
        continue;
    }

    echo "Downloading: {$filename}\n";

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_REFERER => 'https://www.sci.gov.in/cause-list/'
    ]);

    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    curl_close($ch);

    if (
        $content === false ||
        $httpCode !== 200 ||
        strpos($content, '%PDF-') !== 0
    ) {
        echo "Download failed: HTTP {$httpCode}\n";
        $failed++;
        continue;
    }

    file_put_contents($destination, $content);

    $manifest[] = [
        'listing_date' => $date,
        'category' => $category,
        'list_type' => $type,
        'sub_type' => $subType,
        'document_url' => $url,
        'local_file' => $filename,
        'file_size' => filesize($destination),
        'content_type' => $contentType,
        'downloaded_at' => date('c'),
        'status' => 'downloaded'
    ];

    $downloaded++;

    file_put_contents(
        $manifestFile,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

echo "\n================================\n";
echo "DOWNLOAD SUMMARY\n";
echo "================================\n";
echo "Downloaded: {$downloaded}\n";
echo "Already present: {$skipped}\n";
echo "Failed: {$failed}\n";
echo "Manifest: {$manifestFile}\n";
