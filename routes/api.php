<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;



Route::prefix('v6')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forget-password', [AuthController::class, 'forgetPassword']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('profile/update-photo', [AuthController::class, 'updateProfilePhoto'])->name('profile.updatePhoto');
        Route::post('logout', [AuthController::class, 'logout']); // In routes/web.php or routes/api.php
        Route::post('change-password', [AuthController::class, 'changePassword']);

    });
});
