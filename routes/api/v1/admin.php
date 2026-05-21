<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\CandidateUserController;
use App\Http\Controllers\Api\V1\Admin\KycDocumentController;
use App\Http\Controllers\Api\V1\Admin\PackageController;
use App\Http\Controllers\Api\V1\Admin\PaymentController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\SeoSettingsController;
use App\Http\Controllers\Api\V1\Admin\SiteSettingsController;
use App\Http\Controllers\Api\V1\Admin\SocialLoginSettingsController;
use App\Http\Controllers\Api\V1\Admin\SystemHealthController;
use App\Http\Controllers\Api\V1\Admin\TeamUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

$sanctumWithTrackedSession = ['auth:sanctum', 'tracked.session'];

Route::prefix('auth')->group(function () use ($sanctumWithTrackedSession): void {
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
});

Route::middleware($sanctumWithTrackedSession)->group(function (): void {
    Route::get('users', [UserController::class, 'index'])->middleware('permission:admin.users.view');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:admin.users.add');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:admin.users.view');
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->middleware(
        'permission:admin.users.edit'
    );
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:admin.users.delete');

    Route::get('packages/permission-options', [PackageController::class, 'permissionOptions'])->middleware(
        'permission:admin.packages.view'
    );
    Route::get('packages', [PackageController::class, 'index'])->middleware('permission:admin.packages.view');
    Route::get('packages/{package}', [PackageController::class, 'show'])->middleware('permission:admin.packages.view');
    Route::post('packages', [PackageController::class, 'store'])->middleware('permission:admin.packages.add');
    Route::match(['put', 'patch'], 'packages/{package}', [PackageController::class, 'update'])->middleware(
        'permission:admin.packages.edit'
    );
    Route::delete('packages/{package}', [PackageController::class, 'destroy'])->middleware(
        'permission:admin.packages.delete'
    );

    Route::get('subscriptions/active', [AdminSubscriptionController::class, 'active'])->middleware(
        'permission:admin.subscriptions.view'
    );
    Route::get('subscriptions/expiring-soon', [AdminSubscriptionController::class, 'expiringSoon'])->middleware(
        'permission:admin.subscriptions.view'
    );
    Route::get('subscriptions/expired', [AdminSubscriptionController::class, 'expired'])->middleware(
        'permission:admin.subscriptions.view'
    );
    Route::get('subscriptions/history/{user:uuid}', [AdminSubscriptionController::class, 'history'])->middleware(
        'permission:admin.subscriptions.view'
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
    Route::get('system-health', [SystemHealthController::class, 'index'])->middleware(
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

    Route::get('team-users/permission-options', [TeamUserController::class, 'permissionOptions'])->middleware(
        'permission:admin.teams.view'
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

    Route::get('candidates', [CandidateUserController::class, 'index'])->middleware('permission:admin.candidates.view');
    Route::get('candidates/export', [CandidateUserController::class, 'export'])->middleware(
        'permission:admin.candidates.export'
    );
    Route::post('candidates/import', [CandidateUserController::class, 'import'])->middleware(
        'permission:admin.candidates.import'
    );
    Route::get('candidates/import/{importId}', [CandidateUserController::class, 'importStatus'])->middleware(
        'permission:admin.candidates.import'
    );
    Route::post('candidates', [CandidateUserController::class, 'store'])->middleware('permission:admin.candidates.add');
    Route::post('candidates/{user:uuid}/restore', [CandidateUserController::class, 'restore'])->middleware(
        'permission:admin.candidates.edit'
    );
    Route::get('candidates/kyc/pending', [KycDocumentController::class, 'pending'])->middleware(
        'permission:admin.candidates.view'
    );
    Route::patch('candidates/kyc/documents/{document:uuid}', [KycDocumentController::class, 'review'])->middleware(
        'permission:admin.candidates.edit'
    );
    Route::get('candidates/{user:uuid}/kyc/documents', [KycDocumentController::class, 'indexForCandidate'])->middleware(
        'permission:admin.candidates.view'
    );
    Route::post('candidates/{user:uuid}/impersonate', [CandidateUserController::class, 'impersonate'])->middleware(
        'permission:admin.candidates.impersonate'
    );
    Route::get('candidates/{user:uuid}', [CandidateUserController::class, 'show'])->middleware(
        'permission:admin.candidates.view'
    );
    Route::match(['put', 'patch'], 'candidates/{user:uuid}', [CandidateUserController::class, 'update'])->middleware(
        'permission:admin.candidates.edit'
    );
    Route::delete('candidates/{user:uuid}', [CandidateUserController::class, 'destroy'])->middleware(
        'permission:admin.candidates.delete'
    );
    Route::patch('candidates/{user:uuid}/profile-status', [
        CandidateUserController::class,
        'updateProfileStatus',
    ])->middleware('permission:admin.candidates.edit');
    Route::post('candidates/{user:uuid}/publish', [CandidateUserController::class, 'publishProfile'])->middleware(
        'permission:admin.candidates.edit'
    );
    Route::patch('candidates/{user:uuid}/featured', [CandidateUserController::class, 'setFeatured'])->middleware(
        'permission:admin.candidates.feature'
    );
});
