<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\PublicCandidateBrowseController;
use App\Http\Controllers\Api\V1\PublicCandidateProfileController;
use App\Http\Controllers\Api\V1\PublicCandidateProfileOptionsController;
use App\Http\Controllers\Api\V1\PublicFeaturedCandidateController;
use App\Http\Controllers\Api\V1\PublicLegalPageController;
use App\Http\Controllers\Api\V1\PublicSiteSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App public (unauthenticated) routes
|--------------------------------------------------------------------------
|
| Mounted under /api/v1/app/public via routes/api/v1/app.php.
| No auth:sanctum — safe teaser payloads only for candidate browse/detail.
|
*/

Route::middleware('throttle:120,1')->group(function (): void {
    Route::get('featured-candidates', [PublicFeaturedCandidateController::class, 'index']);
    Route::get('candidate-profile-options', PublicCandidateProfileOptionsController::class);
    Route::get('site-settings', [PublicSiteSettingsController::class, 'show']);
    Route::get('legal-pages/{slug}', [PublicLegalPageController::class, 'show']);

    Route::get('candidates/search', [PublicCandidateBrowseController::class, 'search']);
    Route::get('candidates/{candidate:uuid}', [PublicCandidateProfileController::class, 'show']);
});
