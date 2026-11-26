<?php

use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function (): void {
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::post('tokens', [TokenController::class, 'store'])->name('tokens.store');
});

Route::middleware(['signed:relative', 'throttle:6,1'])->group(function (): void {
    Route::post('email-verifications/{user}/{hash}', [EmailVerificationController::class, 'store'])
        ->name('email-verifications.store');
    Route::post('email-changes/{user}/{hash}', [EmailChangeController::class, 'store'])
        ->name('email-changes.store');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::delete('tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');
    Route::apiSingleton('profile', ProfileController::class);
    Route::put('profile/password', [PasswordController::class, 'update'])->name('profile.password.update');
    Route::apiResource('trips', TripController::class);
    Route::apiResource('trips.points', PointController::class)->shallow()->except('index');
    Route::apiResource('segments', SegmentController::class)->only('update');
});
