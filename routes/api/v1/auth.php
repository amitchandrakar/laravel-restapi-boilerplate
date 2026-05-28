<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

$sanctumWithTrackedSession = ['auth:sanctum', 'tracked.session'];

Route::middleware('throttle:api-auth-strict')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware([
    'throttle:api-auth-strict',
    'optional.sanctum',
    'tracked.session',
]);

Route::middleware($sanctumWithTrackedSession)->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('profile', [AuthController::class, 'updateProfile']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
});
