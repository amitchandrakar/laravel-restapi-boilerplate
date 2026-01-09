<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $userService)
    {
        Log::info('UserController constructor called');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();

        return $this->paginatedResponse(UserResource::collection($users), 'Users retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return $this->createdResponse(UserResource::make($user), 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        return $this->successResponse(UserResource::make($user), 'User retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return $this->successResponse(UserResource::make($updatedUser), 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        // dd('HERE');
        $this->userService->deleteUser($user);

        return $this->successResponse(null, 'User deleted successfully');
    }
}
