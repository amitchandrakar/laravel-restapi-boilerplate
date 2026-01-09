<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

// Simulate a request to a non-existent user with a valid-looking UUID
$request = Request::create('/api/v1/users/00000000-0000-0000-0000-000000000000', 'GET');
$request->headers->set('Accept', 'application/json');
$request->headers->set('Authorization', 'Bearer 11|uGvbdTpCPDMdOXhKlC8iFtQNMBjvmqlRz9Wdncwn6d3fc8ac');

$response = $kernel->handle($request);

echo 'Status: ' . $response->getStatusCode() . "\n";
echo 'Content Type: ' . $response->headers->get('Content-Type') . "\n";
echo 'Content: ' . substr($response->getContent(), 0, 500) . "\n"; // Truncate content
