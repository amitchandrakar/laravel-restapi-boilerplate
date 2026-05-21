<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Admin\ListAdminSubscriptionsRequest;
use App\Http\Resources\Api\V1\AdminSubscriptionResource;
use App\Jobs\LogUserActivityJob;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminSubscriptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Read-only admin views for candidate subscriptions.
 */
class AdminSubscriptionController extends Controller
{
    public function __construct(private readonly AdminSubscriptionService $service) {}

    /**
     * Currently active subscriptions.
     */
    public function active(ListAdminSubscriptionsRequest $request): JsonResponse
    {
        return $this->listResponse($request, 'active', fn(array $filters) => $this->service->listActive($filters));
    }

    /**
     * Subscriptions expiring within the next 7 days.
     */
    public function expiringSoon(ListAdminSubscriptionsRequest $request): JsonResponse
    {
        return $this->listResponse(
            $request,
            'expiring_soon',
            fn(array $filters) => $this->service->listExpiringSoon($filters)
        );
    }

    /**
     * Expired subscriptions.
     */
    public function expired(ListAdminSubscriptionsRequest $request): JsonResponse
    {
        return $this->listResponse($request, 'expired', fn(array $filters) => $this->service->listExpired($filters));
    }

    /**
     * Full subscription history for a candidate.
     */
    public function history(ListAdminSubscriptionsRequest $request, User $user): JsonResponse
    {
        try {
            if (!Gate::forUser($request->user())->allows('viewAdminSubscriptions')) {
                return $this->forbiddenResponse();
            }

            $candidate = $this->service->resolveCandidateByUuid($user->uuid);

            if (!($candidate instanceof User)) {
                return $this->notFoundResponse('Candidate not found');
            }

            $paginator = $this->service->historyForUser($candidate, $request->validated());

            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.subscriptions.history',
                'api_v1_admin',
                ['user_id' => $candidate->id],
                $request->ip()
            );

            return $this->paginatedResponse(
                AdminSubscriptionResource::collection($paginator),
                'Subscription history fetched successfully'
            );
        } catch (Throwable $e) {
            Log::error('AdminSubscriptionController@history failed', [
                'user_uuid' => $user->uuid,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  callable(array<string, mixed>): LengthAwarePaginator<int, Subscription>  $fetcher
     */
    private function listResponse(
        ListAdminSubscriptionsRequest $request,
        string $activitySuffix,
        callable $fetcher
    ): JsonResponse {
        try {
            if (!Gate::forUser($request->user())->allows('viewAdminSubscriptions')) {
                return $this->forbiddenResponse();
            }

            $paginator = $fetcher($request->validated());

            LogUserActivityJob::dispatch(
                $this->authenticatedUserId($request),
                'admin.subscriptions.' . $activitySuffix,
                'api_v1_admin',
                ['filters' => $request->validated()],
                $request->ip()
            );

            return $this->paginatedResponse(
                AdminSubscriptionResource::collection($paginator),
                'Subscriptions fetched successfully'
            );
        } catch (Throwable $e) {
            Log::error('AdminSubscriptionController@' . $activitySuffix . ' failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
