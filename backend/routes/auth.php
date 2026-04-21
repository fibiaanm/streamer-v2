<?php

use App\Domain\Auth\Http\Controllers\DeleteAvatarController;
use App\Domain\Auth\Http\Controllers\LoginController;
use App\Domain\Auth\Http\Controllers\LogoutController;
use App\Domain\Auth\Http\Controllers\MeController;
use App\Domain\Auth\Http\Controllers\ProfileController;
use App\Domain\Auth\Http\Controllers\RefreshController;
use App\Domain\Auth\Http\Controllers\RegisterController;
use App\Domain\Auth\Http\Controllers\UpdateProfileController;
use App\Domain\Auth\Http\Controllers\UploadAvatarController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('login',    LoginController::class);
    Route::post('refresh',  RefreshController::class);

    Route::middleware('auth.jwt')->group(function () {
        Route::get('profile',          ProfileController::class);
        Route::patch('profile',        UpdateProfileController::class);
        Route::post('profile/avatar',  UploadAvatarController::class);
        Route::delete('profile/avatar', DeleteAvatarController::class);
        Route::post('logout',          LogoutController::class);

        Route::middleware('enterprise')->group(function () {
            Route::get('me', MeController::class);
        });
    });
});
