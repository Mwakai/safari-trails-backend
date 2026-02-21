<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GroupHikeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PublicCompanyController;
use App\Http\Controllers\PublicGroupHikeController;
use App\Http\Controllers\PublicTrailController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TrailController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Public routes (no authentication required)
Route::prefix('public')->group(function () {
    Route::get('/trails/map', [PublicTrailController::class, 'mapMarkers']);
    Route::get('/trails/regions', [PublicTrailController::class, 'regions']);
    Route::get('/trails/filters', [PublicTrailController::class, 'filters']);
    Route::get('/trails', [PublicTrailController::class, 'index']);
    Route::get('/trails/{slug}', [PublicTrailController::class, 'show']);
    Route::get('/trails/{slug}/related', [PublicTrailController::class, 'related']);

    // Static group hike routes must come BEFORE the {slug} wildcard
    Route::get('/group-hikes/featured', [PublicGroupHikeController::class, 'featured']);
    Route::get('/group-hikes/this-week', [PublicGroupHikeController::class, 'thisWeek']);
    Route::get('/group-hikes/by-company/{companySlug}', [PublicGroupHikeController::class, 'byCompany']);
    Route::get('/group-hikes/by-trail/{trailSlug}', [PublicGroupHikeController::class, 'byTrail']);
    Route::get('/group-hikes', [PublicGroupHikeController::class, 'index']);
    Route::get('/group-hikes/{slug}', [PublicGroupHikeController::class, 'show']);

    Route::get('/companies/{slug}', [PublicCompanyController::class, 'show']);
});

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

    // Companies
    Route::middleware('permission:companies.view,group_hikes.view,group_hikes.view_all')
        ->get('/companies', [CompanyController::class, 'index']);
    Route::middleware('permission:companies.view,group_hikes.view,group_hikes.view_all')
        ->get('/companies/{company}', [CompanyController::class, 'show']);
    Route::middleware('permission:companies.create')
        ->post('/companies', [CompanyController::class, 'store']);
    Route::middleware('permission:companies.update')
        ->match(['put', 'patch'], '/companies/{company}', [CompanyController::class, 'update']);
    Route::middleware('permission:companies.delete')
        ->delete('/companies/{company}', [CompanyController::class, 'destroy']);

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

    Route::get('/trails', [TrailController::class, 'index']);
    Route::post('/trails', [TrailController::class, 'store']);
    Route::get('/trails/regions', [TrailController::class, 'regions']);
    Route::get('/trails/difficulties', [TrailController::class, 'difficulties']);
    Route::get('/trails/{trail}', [TrailController::class, 'show']);
    Route::match(['put', 'patch'], '/trails/{trail}', [TrailController::class, 'update']);
    Route::patch('/trails/{trail}/status', [TrailController::class, 'updateStatus']);
    Route::delete('/trails/{trail}', [TrailController::class, 'destroy']);
    Route::post('/trails/{trail}/restore', [TrailController::class, 'restore']);

    // Group Hikes
    Route::middleware('permission:group_hikes.view,group_hikes.view_all')->group(function () {
        Route::get('/group-hikes', [GroupHikeController::class, 'index']);
        Route::get('/group-hikes/{groupHike}', [GroupHikeController::class, 'show']);
    });
    Route::middleware('permission:group_hikes.create')
        ->post('/group-hikes', [GroupHikeController::class, 'store']);
    Route::middleware('permission:group_hikes.update,group_hikes.update_all')->group(function () {
        Route::match(['put', 'patch'], '/group-hikes/{groupHike}', [GroupHikeController::class, 'update']);
        Route::patch('/group-hikes/{groupHike}/publish', [GroupHikeController::class, 'publish']);
        Route::patch('/group-hikes/{groupHike}/unpublish', [GroupHikeController::class, 'unpublish']);
        Route::patch('/group-hikes/{groupHike}/gallery/reorder', [GroupHikeController::class, 'galleryReorder']);
    });
    Route::middleware('permission:group_hikes.update,group_hikes.update_all')
        ->patch('/group-hikes/{groupHike}/cancel', [GroupHikeController::class, 'cancel']);
    Route::middleware('permission:group_hikes.delete,group_hikes.delete_all')
        ->delete('/group-hikes/{groupHike}', [GroupHikeController::class, 'destroy']);

    Route::middleware('permission:activity_logs.view')
        ->get('/activity-logs', [ActivityLogController::class, 'index']);

    Route::middleware('permission:activity_logs.view')
        ->get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);

    Route::get('/dashboard', function () {});
});
