<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/v1/auth.php'));

Route::prefix('admin')->group(base_path('routes/api/v1/admin.php'));

Route::prefix('app')->group(base_path('routes/api/v1/app.php'));
