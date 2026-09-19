<?php

use App\Http\Controllers\Dev\SignInAsController;
use App\Http\Controllers\Dev\StyleguideController;
use App\Http\Middleware\LocalOnly;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Developer-only routes
|--------------------------------------------------------------------------
|
| Loaded from web.php only in local/testing, and guarded by LocalOnly as a
| second layer. Never available in production.
|
*/

Route::middleware(LocalOnly::class)->prefix('dev/ui')->name('dev.ui.')->group(function () {
    Route::get('/', [StyleguideController::class, 'index'])->name('index');
    Route::get('/delivery', [StyleguideController::class, 'delivery'])->name('delivery');
    Route::get('/sign-in', [StyleguideController::class, 'signIn'])->name('sign-in');
    Route::get('/print/{document}', [StyleguideController::class, 'print'])->name('print');
    Route::view('/phone', 'dev.phone')->name('phone');
    Route::get('/as/{role}', SignInAsController::class)->whereIn('role', ['guest', 'customer', 'delivery'])->name('as');
});
