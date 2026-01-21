<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PDSController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
    
    Route::resource('pds', PDSController::class)->parameters(['pds' => 'pd']);
});
