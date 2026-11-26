<?php

use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\PublicProfileMapController;
use App\Http\Controllers\PublicProfileStatisticsController;
use App\Http\Controllers\PublicProfileTripController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\StatisticsController;
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
    Route::get('places', [PlaceController::class, 'index'])->middleware('throttle:30,1')->name('places.index');
    Route::get('map', [MapController::class, 'show'])->name('map.show');
    Route::get('statistics', [StatisticsController::class, 'show'])->name('statistics.show');
    Route::apiResource('public-profiles', PublicProfileController::class)->only(['index', 'show']);
    Route::apiResource('public-profiles.trips', PublicProfileTripController::class)->only('index');
    Route::get('public-profiles/{public_profile}/map', [PublicProfileMapController::class, 'show'])
        ->name('public-profiles.map.show');
    Route::get('public-profiles/{public_profile}/statistics', [PublicProfileStatisticsController::class, 'show'])
        ->name('public-profiles.statistics.show');
});
