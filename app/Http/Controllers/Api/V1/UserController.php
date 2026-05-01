<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $userService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()?->can('admin.users.view')) {
            return $this->forbiddenResponse();
        }

        $users = $this->userService->getAllUsers();

        return $this->paginatedResponse(UserResource::collection($users), 'Users retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        if (!$request->user()?->can('admin.users.add')) {
            return $this->forbiddenResponse();
        }

        $user = $this->userService->createUser($request->validated());

        return $this->createdResponse(UserResource::make($user), 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.users.view')) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(UserResource::make($user), 'User retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.users.edit')) {
            return $this->forbiddenResponse();
        }

        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return $this->successResponse(UserResource::make($updatedUser), 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->user()?->can('admin.users.delete')) {
            return $this->forbiddenResponse();
        }

        $this->userService->deleteUser($user);

        return $this->successResponse(null, 'User deleted successfully');
    }
}
