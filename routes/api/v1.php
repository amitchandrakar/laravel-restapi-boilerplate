<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\PackageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

Route::middleware('auth:sanctum')->apiResource('users', UserController::class);

Route::prefix('admin')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('packages/permission-options', [PackageController::class, 'permissionOptions'])->middleware(
            'permission:admin.packages.view'
        );
        Route::get('packages', [PackageController::class, 'index'])->middleware('permission:admin.packages.view');
        Route::get('packages/{package}', [PackageController::class, 'show'])->middleware(
            'permission:admin.packages.view'
        );
        Route::post('packages', [PackageController::class, 'store'])->middleware('permission:admin.packages.add');
        Route::match(['put', 'patch'], 'packages/{package}', [PackageController::class, 'update'])->middleware(
            'permission:admin.packages.edit'
        );
        Route::delete('packages/{package}', [PackageController::class, 'destroy'])->middleware(
            'permission:admin.packages.delete'
        );
    });
