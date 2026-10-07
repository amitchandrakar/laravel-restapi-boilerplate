<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Admin\ListModerationReportsRequest;
use App\Jobs\LogUserActivityJob;
use App\Models\ContactRequest;
use App\Models\Favorite;
use App\Models\ProfileDoNotShow;
use App\Models\ProfileSpamReport;
use App\Models\User;
use App\Services\AdminModerationReportService;
use App\Services\ProfileSpamReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ModerationReportController extends Controller
{
    public function __construct(
        private readonly AdminModerationReportService $moderationReportService,
        private readonly ProfileSpamReportService $spamReportService
    ) {}

    public function spamIndex(ListModerationReportsRequest $request): JsonResponse
    {
        return $this->listResponse(
            $request,
            fn(): mixed => $this->moderationReportService->paginateSpamReports($request->validated()),
            'admin.moderation_reports.spam.index'
        );
    }

    public function favoritesIndex(ListModerationReportsRequest $request): JsonResponse
    {
        return $this->listResponse(
            $request,
            fn(): mixed => $this->moderationReportService->paginateFavoriteReports($request->validated()),
            'admin.moderation_reports.favorites.index'
        );
    }

    public function contactRequestsIndex(ListModerationReportsRequest $request): JsonResponse
    {
        return $this->listResponse(
            $request,
            fn(): mixed => $this->moderationReportService->paginateContactRequestReports($request->validated()),
            'admin.moderation_reports.contact_requests.index'
        );
    }

    public function dontShowAgainIndex(ListModerationReportsRequest $request): JsonResponse
    {
        return $this->listResponse(
            $request,
            fn(): mixed => $this->moderationReportService->paginateDontShowAgainReports($request->validated()),
            'admin.moderation_reports.dont_show_again.index'
        );
    }

    public function markSpammer(Request $request, ProfileSpamReport $report): JsonResponse
    {
        return $this->actionResponse(
            $request,
            function () use ($request, $report): array {
                $updated = $this->spamReportService->markSpammer($report, $this->adminUser($request));

                return [
                    'uuid' => $updated->uuid,
                    'status' => $updated->status,
                    'reviewedAt' => $updated->reviewed_at?->toIso8601String(),
                ];
            },
            'admin.moderation_reports.spam.mark_spammer',
            ['report_uuid' => $report->uuid],
            'Spam report resolved — profile marked as spam'
        );
    }

    public function markNotSpammer(Request $request, ProfileSpamReport $report): JsonResponse
    {
        return $this->actionResponse(
            $request,
            function () use ($request, $report): array {
                $updated = $this->spamReportService->markNotSpammer($report, $this->adminUser($request));

                return [
                    'uuid' => $updated->uuid,
                    'status' => $updated->status,
                    'reviewedAt' => $updated->reviewed_at?->toIso8601String(),
                ];
            },
            'admin.moderation_reports.spam.mark_not_spammer',
            ['report_uuid' => $report->uuid],
            'Spam report dismissed'
        );
    }

    public function unmarkFavorite(Request $request, Favorite $favorite): JsonResponse
    {
        return $this->actionResponse(
            $request,
            function () use ($favorite): array {
                $updated = $this->moderationReportService->unmarkFavorite($favorite);

                return [
                    'uuid' => $updated->uuid,
                    'deletedAt' => $updated->deleted_at?->toIso8601String(),
                ];
            },
            'admin.moderation_reports.favorites.unmark',
            ['favorite_uuid' => $favorite->uuid],
            'Favorite removed'
        );
    }

    public function unmarkDontShowAgain(Request $request, ProfileDoNotShow $hide): JsonResponse
    {
        return $this->actionResponse(
            $request,
            function () use ($hide): array {
                $uuid = $hide->uuid;
                $this->moderationReportService->unmarkDontShowAgain($hide);

                return [
                    'uuid' => $uuid,
                ];
            },
            'admin.moderation_reports.dont_show_again.unmark',
            ['hide_uuid' => $hide->uuid],
            'Don’t show again mark removed'
        );
    }

    public function markContacted(Request $request, ContactRequest $contactRequest): JsonResponse
    {
        return $this->resolveContactRequest($request, $contactRequest, 'contacted');
    }

    public function markNotContacted(Request $request, ContactRequest $contactRequest): JsonResponse
    {
        return $this->resolveContactRequest($request, $contactRequest, 'not_contacted');
    }

    private function resolveContactRequest(
        Request $request,
        ContactRequest $contactRequest,
        string $resolution
    ): JsonResponse {
        $action =
            $resolution === 'contacted'
                ? 'admin.moderation_reports.contact_requests.mark_contacted'
                : 'admin.moderation_reports.contact_requests.mark_not_contacted';
        $message =
            $resolution === 'contacted'
                ? 'Contact request marked as contacted'
                : 'Contact request marked as not contacted';

        return $this->actionResponse(
            $request,
            function () use ($request, $contactRequest, $resolution): array {
                $updated =
                    $resolution === 'contacted'
                        ? $this->moderationReportService->markContacted($contactRequest, $this->adminUser($request))
                        : $this->moderationReportService->markNotContacted($contactRequest, $this->adminUser($request));

                return [
                    'uuid' => $updated->uuid,
                    'adminResolution' => $updated->admin_resolution,
                    'adminResolvedAt' => $updated->admin_resolved_at?->toIso8601String(),
                ];
            },
            $action,
            ['contact_request_uuid' => $contactRequest->uuid],
            $message
        );
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function listResponse(
        ListModerationReportsRequest $request,
        callable $callback,
        string $activity
    ): JsonResponse {
        try {
            if (!$request->user()?->can('admin.moderation_reports.view')) {
                return $this->forbiddenResponse();
            }

            $paginator = $callback();

            LogUserActivityJob::dispatch(
                $request->user()->id,
                $activity,
                'api_v1_admin',
                ['filters' => $request->validated()],
                $request->ip()
            );

            return $this->paginatedResponse($paginator, 'Moderation reports fetched successfully');
        } catch (Throwable $e) {
            Log::error('ModerationReportController@list failed', [
                'activity' => $activity,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function adminUser(Request $request): User
    {
        $admin = $request->user();

        if (!($admin instanceof User)) {
            throw ValidationException::withMessages([
                'user' => ['Unauthenticated.'],
            ]);
        }

        return $admin;
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     * @param  array<string, mixed>  $context
     */
    private function actionResponse(
        Request $request,
        callable $callback,
        string $activity,
        array $context,
        string $message
    ): JsonResponse {
        try {
            if (!$request->user()?->can('admin.moderation_reports.action')) {
                return $this->forbiddenResponse();
            }

            $data = $callback();

            LogUserActivityJob::dispatch($request->user()->id, $activity, 'api_v1_admin', $context, $request->ip());

            return $this->successResponse($data, $message);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            Log::error('ModerationReportController@action failed', [
                'activity' => $activity,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
