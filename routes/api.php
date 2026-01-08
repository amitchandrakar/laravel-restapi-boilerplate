<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test route
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working',
        'version' => 'v1',
    ]);
});

// Health check endpoint (no auth required)
Route::get('health', function () {
    return response()->json([
        'status' => 'up',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Load V1 routes
Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// Fallback for undefined routes
Route::fallback(function () {
    return response()->json(
        [
            'success' => false,
            'message' => 'Endpoint not found',
        ],
        404
    );
});
