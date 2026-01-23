<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Public routes (no authentication required)
Route::prefix('public')->group(function () {});

// Admin routes (authentication required for CMS)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('permission:users.create')
        ->post('/users', [UserController::class, 'store']);

    Route::middleware('permission:users.view')
        ->get('/users', [AuthController::class, 'getAllUsers']);

    Route::middleware('permission:users.view')
        ->get('/users/{user}', [UserController::class, 'show']);

    Route::middleware('permission:users.update')
        ->match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);

    Route::middleware('permission:users.delete')
        ->delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/dashboard', function () {});
});
