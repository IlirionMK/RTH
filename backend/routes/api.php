<?php

use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function (): void {
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::post('tokens', [TokenController::class, 'store'])->name('tokens.store');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::delete('tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');
    Route::apiSingleton('profile', ProfileController::class);
    Route::put('profile/password', [PasswordController::class, 'update'])->name('profile.password.update');
});
