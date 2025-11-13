<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Public routes (no authentication required)
Route::prefix('public')->group(function () {

});

// Admin routes (authentication required for CMS)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/users', [AuthController::class, 'getAllUsers']);

    Route::get('/dashboard', function () {
    });

});