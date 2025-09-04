<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Named login route for API authentication redirects
Route::get('/login', function () {
    return response()->json([
        'message' => 'Unauthenticated. Please log in via the API.',
        'login_url' => '/api/login',
    ], 401);
})->name('login');
