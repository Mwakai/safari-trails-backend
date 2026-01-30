<?php

use App\Http\Controllers\AmenityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\RoleController;
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
        ->get('/users', [UserController::class, 'index']);

    Route::middleware('permission:users.view')
        ->get('/users/{user}', [UserController::class, 'show']);

    Route::middleware('permission:users.update')
        ->match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);

    Route::middleware('permission:users.delete')
        ->delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);

    Route::get('/amenities', [AmenityController::class, 'index']);
    Route::post('/amenities', [AmenityController::class, 'store']);
    Route::get('/amenities/{amenity}', [AmenityController::class, 'show']);
    Route::match(['put', 'patch'], '/amenities/{amenity}', [AmenityController::class, 'update']);
    Route::delete('/amenities/{amenity}', [AmenityController::class, 'destroy']);

    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media/{media}', [MediaController::class, 'show']);
    Route::match(['put', 'patch'], '/media/{media}', [MediaController::class, 'update']);
    Route::delete('/media/{media}', [MediaController::class, 'destroy']);

    Route::get('/dashboard', function () {});
});
