<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\CandidateUserController;
use App\Http\Controllers\Api\V1\Admin\PackageController;
use App\Http\Controllers\Api\V1\Admin\TeamUserController;
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

        Route::get('team-users', [TeamUserController::class, 'index'])->middleware('permission:admin.teams.view');
        Route::post('team-users', [TeamUserController::class, 'store'])->middleware('permission:admin.teams.add');
        Route::get('team-users/{user}', [TeamUserController::class, 'show'])->middleware('permission:admin.teams.view');
        Route::match(['put', 'patch'], 'team-users/{user}', [TeamUserController::class, 'update'])->middleware(
            'permission:admin.teams.edit'
        );
        Route::delete('team-users/{user}', [TeamUserController::class, 'destroy'])->middleware(
            'permission:admin.teams.delete'
        );

        Route::get('candidates', [CandidateUserController::class, 'index'])->middleware(
            'permission:admin.candidates.view'
        );
        Route::post('candidates', [CandidateUserController::class, 'store'])->middleware(
            'permission:admin.candidates.add'
        );
        Route::get('candidates/{user}', [CandidateUserController::class, 'show'])->middleware(
            'permission:admin.candidates.view'
        );
        Route::match(['put', 'patch'], 'candidates/{user}', [CandidateUserController::class, 'update'])->middleware(
            'permission:admin.candidates.edit'
        );
        Route::delete('candidates/{user}', [CandidateUserController::class, 'destroy'])->middleware(
            'permission:admin.candidates.delete'
        );
    });
