<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\CandidateUserController;
use App\Http\Controllers\Api\V1\Admin\KycDocumentController;
use App\Http\Controllers\Api\V1\Admin\PackageController;
use App\Http\Controllers\Api\V1\Admin\PaymentController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\SeoSettingsController;
use App\Http\Controllers\Api\V1\Admin\SiteSettingsController;
use App\Http\Controllers\Api\V1\Admin\SocialLoginSettingsController;
use App\Http\Controllers\Api\V1\Admin\TeamUserController;
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
use App\Http\Controllers\Api\V1\RegistrationPaymentController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

$sanctumWithTrackedSession = ['auth:sanctum', 'tracked.session'];

Route::prefix('public')
    ->middleware('throttle:120,1')
    ->group(function () {
        Route::get('featured-candidates', [PublicFeaturedCandidateController::class, 'index']);
        Route::get('candidate-profile-options', PublicCandidateProfileOptionsController::class);
    });

Route::post('payment/razorpay/webhook', [RegistrationPaymentController::class, 'webhook'])->middleware(
    'throttle:120,1'
);

Route::post('webhooks/razorpay', [RegistrationPaymentController::class, 'webhook'])->middleware('throttle:120,1');

Route::prefix('me')
    ->middleware(array_merge($sanctumWithTrackedSession, ['profile.uuid.header']))
    ->group(function () {
        Route::post('registration/checkout', [MeRegistrationController::class, 'checkout']);
        Route::post('registration/payments/verify', [MeRegistrationController::class, 'verify']);
        Route::get('registration/status', [MeRegistrationController::class, 'status']);

        Route::get('kyc/documents', [MeKycController::class, 'documents']);
        Route::post('kyc/upload-sessions', [MeKycController::class, 'uploadSessions']);
        Route::post('kyc/upload', [MeKycController::class, 'upload']);
        Route::post('kyc/submit', [MeKycController::class, 'submit']);

        Route::put('devices', [MeDeviceController::class, 'update']);
    });

Route::prefix('auth')->group(function () use ($sanctumWithTrackedSession) {
    Route::get('registration', [AuthController::class, 'registrationOptions']);
    Route::post('register-candidate', [AuthController::class, 'registerCandidate']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware([
        'optional.sanctum',
        'tracked.session',
    ]);

    Route::middleware($sanctumWithTrackedSession)->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);

        Route::post('payment/registration/confirm', [RegistrationPaymentController::class, 'confirm']);
        Route::get('payment/registration/{paymentUuid}/status', [
            RegistrationPaymentController::class,
            'status',
        ])->whereUuid('paymentUuid');

        Route::prefix('notifications')
            ->middleware('throttle:60,1')
            ->group(function () {
                Route::get('/', [MemberNotificationController::class, 'index']);
                Route::get('summary', [MemberNotificationController::class, 'summary']);
                Route::post('read-all', [MemberNotificationController::class, 'markAllRead']);
                Route::get('{notificationId}', [MemberNotificationController::class, 'show']);
                Route::patch('{notificationId}/read', [MemberNotificationController::class, 'markRead']);
            });

        Route::prefix('candidate/profile')->group(function () {
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

        Route::prefix('candidate/kyc')->group(function () {
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

Route::middleware($sanctumWithTrackedSession)->group(function () {
    Route::get('users', [UserController::class, 'index'])->middleware('permission:admin.users.view');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:admin.users.add');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:admin.users.view');
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->middleware(
        'permission:admin.users.edit'
    );
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:admin.users.delete');
});

Route::prefix('admin')
    ->middleware($sanctumWithTrackedSession)
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
        Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:admin.payments.view');
        Route::get('payments/{payment:uuid}', [PaymentController::class, 'show'])->middleware(
            'permission:admin.payments.view'
        );
        Route::post('payments', [PaymentController::class, 'store'])->middleware('permission:admin.payments.add');
        Route::match(['put', 'patch'], 'payments/{payment:uuid}', [PaymentController::class, 'update'])->middleware(
            'permission:admin.payments.edit'
        );
        Route::delete('payments/{payment:uuid}', [PaymentController::class, 'destroy'])->middleware(
            'permission:admin.payments.delete'
        );
        Route::get('reports/candidates/area', [ReportController::class, 'candidatesByArea'])->middleware(
            'permission:admin.reports.state.view'
        );
        Route::get('reports/candidates/surname', [ReportController::class, 'candidatesBySurname'])->middleware(
            'permission:admin.reports.community.view'
        );
        Route::get('reports/candidates/education', [ReportController::class, 'candidatesByEducation'])->middleware(
            'permission:admin.reports.education.view'
        );
        Route::get('reports/active-users', [ReportController::class, 'activeUsers'])->middleware(
            'permission:admin.reports.active_users.view'
        );
        Route::get('reports/user-activities', [ReportController::class, 'userActivities'])->middleware(
            'permission:admin.reports.user_activities.view'
        );
        Route::get('reports/team-activities', [ReportController::class, 'teamActivities'])->middleware(
            'permission:admin.reports.team_activities.view'
        );
        Route::get('dashboard/stats', [ReportController::class, 'dashboardStats'])->middleware(
            'permission:admin.dashboard.view'
        );
        Route::get('settings/seo', [SeoSettingsController::class, 'show'])->middleware(
            'permission:admin.settings.seo.view'
        );
        Route::get('settings/site', [SiteSettingsController::class, 'show'])->middleware(
            'permission:admin.settings.site.view'
        );
        Route::put('settings/seo', [SeoSettingsController::class, 'update'])->middleware(
            'permission:admin.settings.seo.edit'
        );
        Route::put('settings/site', [SiteSettingsController::class, 'update'])->middleware(
            'permission:admin.settings.site.edit'
        );
        Route::get('settings/social-login', [SocialLoginSettingsController::class, 'show'])->middleware(
            'permission:admin.settings.social.view'
        );
        Route::put('settings/social-login', [SocialLoginSettingsController::class, 'update'])->middleware(
            'permission:admin.settings.social.edit'
        );
        Route::get('settings/roles', [AdminRoleController::class, 'index'])->middleware(
            'permission:admin.settings.roles.view'
        );
        Route::post('settings/roles', [AdminRoleController::class, 'store'])->middleware(
            'permission:admin.settings.roles.edit'
        );
        Route::get('settings/roles/{role:uuid}/permissions', [AdminRoleController::class, 'permissions'])->middleware(
            'permission:admin.settings.roles.view'
        );
        Route::put('settings/roles/{role:uuid}', [AdminRoleController::class, 'update'])->middleware(
            'permission:admin.settings.roles.edit'
        );
        Route::delete('settings/roles/{role:uuid}', [AdminRoleController::class, 'destroy'])->middleware(
            'permission:admin.settings.roles.edit'
        );

        Route::get('team-users', [TeamUserController::class, 'index'])->middleware('permission:admin.teams.view');
        Route::post('team-users', [TeamUserController::class, 'store'])->middleware('permission:admin.teams.add');
        Route::get('team-users/{user:uuid}', [TeamUserController::class, 'show'])->middleware(
            'permission:admin.teams.view'
        );
        Route::match(['put', 'patch'], 'team-users/{user:uuid}', [TeamUserController::class, 'update'])->middleware(
            'permission:admin.teams.edit'
        );
        Route::delete('team-users/{user:uuid}', [TeamUserController::class, 'destroy'])->middleware(
            'permission:admin.teams.delete'
        );

        Route::get('candidates', [CandidateUserController::class, 'index'])->middleware(
            'permission:admin.candidates.view'
        );
        Route::post('candidates', [CandidateUserController::class, 'store'])->middleware(
            'permission:admin.candidates.add'
        );
        Route::put('candidates/profile', [CandidateUserController::class, 'saveCompleteProfile'])->middleware(
            'permission:admin.candidates.edit'
        );
        Route::get('candidates/kyc/pending', [KycDocumentController::class, 'pending'])->middleware(
            'permission:admin.candidates.view'
        );
        Route::patch('candidates/kyc/documents/{document:uuid}', [KycDocumentController::class, 'review'])->middleware(
            'permission:admin.candidates.edit'
        );
        Route::get('candidates/{user:uuid}/profile-details', [CandidateUserController::class, 'profileDetails']);
        Route::get('candidates/{user:uuid}', [CandidateUserController::class, 'show']);
        // Route::get('candidates/{user:uuid}', [CandidateUserController::class, 'show'])->middleware(
        //     'permission:admin.candidates.view'
        // );
        Route::match(['put', 'patch'], 'candidates/{user:uuid}', [
            CandidateUserController::class,
            'update',
        ])->middleware('permission:admin.candidates.edit');
        Route::delete('candidates/{user:uuid}', [CandidateUserController::class, 'destroy'])->middleware(
            'permission:admin.candidates.delete'
        );
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/photos', [
            CandidateUserController::class,
            'savePhotos',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/personal-details', [
            CandidateUserController::class,
            'savePersonalDetails',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/horoscope', [
            CandidateUserController::class,
            'saveHoroscope',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/location-family-roots', [
            CandidateUserController::class,
            'saveLocationFamilyRoots',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/career-education', [
            CandidateUserController::class,
            'saveCareerEducation',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/family-background', [
            CandidateUserController::class,
            'saveFamilyBackground',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/lifestyle', [
            CandidateUserController::class,
            'saveLifestyle',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/partner-preferences', [
            CandidateUserController::class,
            'savePartnerPreferences',
        ])->middleware('permission:admin.candidates.edit');
        Route::match(['put', 'patch'], 'candidates/{user:uuid}/sections/preferences', [
            CandidateUserController::class,
            'savePreferences',
        ]);
        Route::get('candidates/{user:uuid}/section-progress', [
            CandidateUserController::class,
            'sectionProgress',
        ])->middleware('permission:admin.candidates.view');
        Route::post('candidates/{user:uuid}/publish', [CandidateUserController::class, 'publishProfile'])->middleware(
            'permission:admin.candidates.edit'
        );
        Route::patch('candidates/{user:uuid}/featured', [CandidateUserController::class, 'setFeatured'])->middleware(
            'permission:admin.candidates.feature'
        );
        Route::put('candidates/{user:uuid}/profile', [CandidateUserController::class, 'saveFullProfile'])->middleware(
            'permission:admin.candidates.edit'
        );
    });
