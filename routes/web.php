<?php

use Illuminate\Support\Facades\Route;

// Dummy login route to prevent "Route [login] not defined" error
Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated access. Please log in to continue.',
    ], 401);
})->name('login');
