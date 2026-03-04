<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\CoinPackController;
use App\Http\Controllers\Api\FlatController;
use App\Http\Controllers\Api\TenantFlatRentRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/verify-otp', [RegisterController::class, 'verifyOtp']);
    Route::post('/select-user-type', [RegisterController::class, 'selectUserType']);
    Route::get('public/coin-packs', [CoinPackController::class, 'list']);
    Route::get('public/flats/available', [FlatController::class, 'availableList']);
    Route::get('public/flats/available/{id}', [FlatController::class, 'availableDetails']);

    Route::middleware('auth:sanctum')->group(function () {
        // Common (all authenticated users)
        Route::get('profile', [AuthController::class, 'profile']);
        Route::get('user/assigned-roles', [AuthController::class, 'assignedRoleList']);
        Route::post('user/switch-role', [AuthController::class, 'switchRole']);
        Route::post('profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('profile/update-photo', [AuthController::class, 'updateProfilePhoto'])->name('profile.updatePhoto');
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);

        Route::get('locations/divisions', [BuildingController::class, 'divisions']);
        Route::get('locations/districts', [BuildingController::class, 'districts']);
        Route::get('locations/thanas', [BuildingController::class, 'thanas']);
        Route::get('locations/areas', [BuildingController::class, 'areas']);
        Route::get('locations/area-nodes', [BuildingController::class, 'areaNodes']);
        // Tenant APIs
        Route::prefix('tenant')->group(function () {
            Route::post('rent-requests', [TenantFlatRentRequestController::class, 'tenantRequestStore']);
            Route::get('rent-requests', [TenantFlatRentRequestController::class, 'tenantRequestList']);
        });

        // Landowner APIs
        Route::prefix('landowner')->group(function () {
            Route::get('rent-requests', [TenantFlatRentRequestController::class, 'landownerRequestList']);
            Route::post('rent-requests/{id}/action', [TenantFlatRentRequestController::class, 'landownerRequestAction']);
            Route::get('buildings', [BuildingController::class, 'index']);
            Route::post('buildings', [BuildingController::class, 'store']);
            Route::get('buildings/{id}', [BuildingController::class, 'show']);
            Route::post('buildings/{id}', [BuildingController::class, 'update']);
            Route::delete('buildings/{id}', [BuildingController::class, 'destroy']);
            Route::get('buildings/{buildingId}/flats', [FlatController::class, 'indexByBuilding']);
            Route::post('buildings/{buildingId}/flats', [FlatController::class, 'store']);
            Route::get('flats/available', [FlatController::class, 'availableList']);
            Route::get('flats/available/{id}', [FlatController::class, 'availableDetails']);
            Route::get('flats/{id}', [FlatController::class, 'show']);
            Route::post('flats/{id}', [FlatController::class, 'update']);
            Route::delete('flats/{id}', [FlatController::class, 'destroy']);
        });

        // Service Provider APIs
        Route::prefix('service-provider')->group(function () {
            
        });
    });
});
