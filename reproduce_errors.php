<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

function testRequest($app, $kernel, $path, $method = 'GET', $headers = [])
{
    echo "--- Testing $method $path ---\n";
    $request = Request::create($path, $method);
    $request->headers->set('Accept', 'application/json');
    foreach ($headers as $k => $v) {
        $request->headers->set($k, $v);
    }

    // For specific tests we might need to mock behavior or hit specific routes.
    // However, it's easier to just throw exceptions in a test route closure if possible.
    // But we can't easily modify routes here.

    // Instead, let's just use the exception handler directly to render exceptions?
    // That's more unit-testy. For integration, let's try to hit real routes.

    try {
        $response = $kernel->handle($request);
    } catch (\Throwable $e) {
        // If the kernel doesn't catch it (it should), we catch it here.
        echo 'Exception escaped Kernel: ' . get_class($e) . "\n";

        return;
    }

    echo 'Status: ' . $response->getStatusCode() . "\n";
    echo 'Content: ' . substr($response->getContent(), 0, 300) . "\n";
}

// 1. Authentication (401) - protected route without token
testRequest($app, $kernel, '/api/v1/auth/me');

// 2. Validation (422) - login without data
testRequest($app, $kernel, '/api/v1/auth/login', 'POST');

// 3. Method Not Allowed (405) - POST to GET route
testRequest($app, $kernel, '/api/v1/auth/me', 'POST');

// 4. Generic 500 - We need a route that crashes or we can mock the exception handler to throw?
// Let's just rely on the above for now.
