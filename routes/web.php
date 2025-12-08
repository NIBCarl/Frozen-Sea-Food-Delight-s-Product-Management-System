<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SocialAuthController;

// Public routes (no authentication required)
Route::get('/', function () {
    return view('welcome-new');
});

Route::get('/public/products', function () {
    return view('public-products');
});

// Google OAuth Routes (must be web routes for redirect to work)
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// SPA catch-all route for authenticated users
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
