<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php
Route::get('/login', function () {
    Log::error('Redirect to login route detected!', [
        'previous_url' => url()->previous(),
        'full_url' => request()->fullUrl(),
        'headers' => request()->headers->all(),
        'method' => request()->method(),
        'intended_url' => session()->get('url.intended')
    ]);
    
    return response()->json(['message' => 'Token missing'], 401);
})->name('login');
