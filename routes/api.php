<?php

use App\Support\ApiResponseBuilder;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test route
Route::get('/', function () {
    return ApiResponseBuilder::success(['version' => 'v1'], 'API is working', 200);
});

// Health check endpoints (no auth required)
Route::get('health', [\App\Http\Controllers\Api\HealthController::class, 'check']);
Route::get('health/detailed', [\App\Http\Controllers\Api\HealthController::class, 'detailed']);

// Load V1 routes
Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// Fallback for undefined routes
Route::fallback(function () {
    return ApiResponseBuilder::error(
        'Endpoint not found',
        404,
        ApiResponseBuilder::ERROR_NOT_FOUND,
        'Endpoint not found',
        null
    );
});
