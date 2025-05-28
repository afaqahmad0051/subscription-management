<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserSubscriptionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Admin subscription routes
    Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/statistics', 'statistics');
        Route::get('/{subscription}', 'show');
    });

    // User subscription routes
    Route::prefix('user/subscriptions')->controller(UserSubscriptionController::class)->group(function () {
        Route::get('', 'index');
        Route::post('', 'subscribe');
        Route::delete('/{subscription}/cancel', 'cancel');
        Route::patch('/{subscription}/auto-renew', 'toggleAutoRenew');
        Route::get('/plans', 'plans');
    });
});
