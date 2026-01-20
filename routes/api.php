<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Public routes (no authentication required)
Route::prefix('public')->group(function () {});

// Admin routes (authentication required for CMS)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('permission:users.create')
        ->post('/register', [AuthController::class, 'register']);

    Route::middleware('permission:users.view')
        ->get('/users', [AuthController::class, 'getAllUsers']);

    Route::get('/dashboard', function () {});
});
