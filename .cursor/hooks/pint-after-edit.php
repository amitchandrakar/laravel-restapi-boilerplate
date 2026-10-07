#!/usr/bin/env php
<?php

declare(strict_types=1);

$raw = stream_get_contents(STDIN);
$data = json_decode(is_string($raw) ? $raw : '', true);

if (!is_array($data)) {
    echo "{}\n";
    exit(0);
}

$path = '';

foreach (['file_path', 'path', 'filePath', 'uri'] as $key) {
    if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
        $path = $data[$key];

        break;
    }
}

if ($path === '' || !str_ends_with(strtolower($path), '.php')) {
    echo "{}\n";
    exit(0);
}

if (!is_file($path)) {
    echo "{}\n";
    exit(0);
}

$root = dirname(__DIR__, 2);
$pint = $root . '/vendor/bin/pint';

if (!is_file($pint)) {
    echo "{}\n";
    exit(0);
}

$cmd = escapeshellarg($pint) . ' ' . escapeshellarg($path);
exec($cmd . ' 2>&1', $output, $code);

if ($code !== 0) {
    $message = 'Pint failed for ' . $path . '. Fix style before continuing.';
    echo json_encode(['additional_context' => $message], JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "{}\n";
exit(0);
