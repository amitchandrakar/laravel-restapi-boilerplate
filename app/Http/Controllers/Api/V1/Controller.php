<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use ApiResponse, AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        // Check if user is authenticated (e.g. for optional auth logic in subclasses)
        if (auth()->check()) {
            // User is logged in; subclasses may use auth()->user()
        }
    }

    protected function authenticatedUserId(Request $request): int
    {
        $user = $request->user();

        if (!($user instanceof User)) {
            abort(401, 'Unauthenticated');
        }

        return $user->id;
    }
}
