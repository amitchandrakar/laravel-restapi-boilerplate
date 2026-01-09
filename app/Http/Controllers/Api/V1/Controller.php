<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use ApiResponse, AuthorizesRequests, ValidatesRequests;
}
