<?php

use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::get('/', function () {
    return view('welcome-new');
});

Route::get('/public/products', function () {
    return view('public-products');
});

// SPA catch-all route for authenticated users
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
