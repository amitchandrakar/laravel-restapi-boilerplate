<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CandidateContactRequestController;
use App\Http\Controllers\Api\V1\CandidateDiscoveryController;
use App\Http\Controllers\Api\V1\CandidateKycController;
use App\Http\Controllers\Api\V1\CandidateProfileController;
use App\Http\Controllers\Api\V1\MeDeviceController;
use App\Http\Controllers\Api\V1\MeKycController;
use App\Http\Controllers\Api\V1\MemberNotificationController;
use App\Http\Controllers\Api\V1\MeRegistrationController;
use App\Http\Controllers\Api\V1\PublicCandidateProfileOptionsController;
use App\Http\Controllers\Api\V1\PublicFeaturedCandidateController;
use App\Http\Controllers\Api\V1\PublicLegalPageController;
use App\Http\Controllers\Api\V1\PublicSiteSettingsController;
use App\Http\Controllers\Api\V1\RegistrationPaymentController;
use Illuminate\Support\Facades\Route;

$sanctumWithTrackedSession = ['auth:sanctum', 'tracked.session'];

Route::prefix('public')
    ->middleware('throttle:120,1')
    ->group(function (): void {
        Route::get('featured-candidates', [PublicFeaturedCandidateController::class, 'index']);
        Route::get('candidate-profile-options', PublicCandidateProfileOptionsController::class);
        Route::get('site-settings', [PublicSiteSettingsController::class, 'show']);
        Route::get('legal-pages/{slug}', [PublicLegalPageController::class, 'show']);
    });

Route::post('payment/razorpay/webhook', [RegistrationPaymentController::class, 'webhook'])->middleware(
    'throttle:120,1'
);

Route::post('webhooks/razorpay', [RegistrationPaymentController::class, 'webhook'])->middleware('throttle:120,1');

Route::prefix('me')
    ->middleware(array_merge($sanctumWithTrackedSession, ['profile.uuid.header']))
    ->group(function (): void {
        Route::post('registration/checkout', [MeRegistrationController::class, 'checkout']);
        Route::post('registration/payments/verify', [MeRegistrationController::class, 'verify']);
        Route::get('registration/status', [MeRegistrationController::class, 'status']);

        Route::get('kyc/documents', [MeKycController::class, 'documents']);
        Route::post('kyc/upload-sessions', [MeKycController::class, 'uploadSessions']);
        Route::post('kyc/upload', [MeKycController::class, 'upload']);
        Route::post('kyc/submit', [MeKycController::class, 'submit']);

        Route::put('devices', [MeDeviceController::class, 'update']);
    });

Route::prefix('auth')->group(function () use ($sanctumWithTrackedSession): void {
    Route::get('registration', [AuthController::class, 'registrationOptions'])->middleware('throttle:api-general');

    Route::middleware('throttle:api-auth-strict')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('register-candidate', [AuthController::class, 'registerCandidate']);
    });

    Route::middleware($sanctumWithTrackedSession)->group(function (): void {
        Route::post('payment/registration/confirm', [RegistrationPaymentController::class, 'confirm']);
        Route::get('payment/registration/{paymentUuid}/status', [
            RegistrationPaymentController::class,
            'status',
        ])->whereUuid('paymentUuid');

        Route::prefix('notifications')
            ->middleware('throttle:api-general')
            ->group(function (): void {
                Route::get('/', [MemberNotificationController::class, 'index']);
                Route::get('summary', [MemberNotificationController::class, 'summary']);
                Route::post('read-all', [MemberNotificationController::class, 'markAllRead']);
                Route::get('{notificationId}', [MemberNotificationController::class, 'show']);
                Route::patch('{notificationId}/read', [MemberNotificationController::class, 'markRead']);
            });

        Route::prefix('candidate/profile')->group(function (): void {
            Route::get('details', [CandidateProfileController::class, 'profileDetails']);
            Route::put('details', [CandidateProfileController::class, 'saveFullProfile']);
            Route::patch('preferences', [CandidateProfileController::class, 'savePreferences']);
            Route::patch('basics', [CandidateProfileController::class, 'saveBasics']);
            Route::patch('photos', [CandidateProfileController::class, 'savePhotos']);
            Route::post('photos/upload', [CandidateProfileController::class, 'uploadPhoto']);
            Route::patch('personal-details', [CandidateProfileController::class, 'savePersonalDetails']);
            Route::patch('horoscope', [CandidateProfileController::class, 'saveHoroscope']);
            Route::patch('location-family-roots', [CandidateProfileController::class, 'saveLocationFamilyRoots']);
            Route::patch('career-education', [CandidateProfileController::class, 'saveCareerEducation']);
            Route::patch('family-background', [CandidateProfileController::class, 'saveFamilyBackground']);
            Route::patch('lifestyle', [CandidateProfileController::class, 'saveLifestyle']);
            Route::patch('partner-preferences', [CandidateProfileController::class, 'savePartnerPreferences']);
            Route::get('progress', [CandidateProfileController::class, 'progress']);
            Route::post('publish', [CandidateProfileController::class, 'publish']);
        });

        Route::get('candidate/{candidate:uuid}/profile-details', [
            CandidateProfileController::class,
            'peerProfileDetails',
        ]);

        Route::prefix('candidate/kyc')->group(function (): void {
            Route::get('documents', [CandidateKycController::class, 'index']);
            Route::put('documents', [CandidateKycController::class, 'upsert']);
        });

        Route::get('candidate/{candidate:uuid}/photos', [CandidateProfileController::class, 'listPhotos']);
        Route::patch('candidate/{candidate:uuid}/photos/{imageUuid}', [
            CandidateProfileController::class,
            'setProfilePhoto',
        ])->whereUuid('imageUuid');
        Route::delete('candidate/{candidate:uuid}/photos/{imageUuid}', [
            CandidateProfileController::class,
            'deletePhoto',
        ])->whereUuid('imageUuid');
        Route::get('candidate/search', [CandidateDiscoveryController::class, 'browse']);
        Route::get('candidate/favorites', [CandidateDiscoveryController::class, 'favorites']);
        Route::patch('candidate/favorites/{user:uuid}', [CandidateDiscoveryController::class, 'toggleFavorite']);
        Route::get('candidate/matches', [CandidateDiscoveryController::class, 'matches']);

        Route::post('candidate/contact-requests', [CandidateContactRequestController::class, 'store']);
        Route::patch('candidate/contact-requests/{contactRequest}', [
            CandidateContactRequestController::class,
            'respond',
        ]);
    });
});
