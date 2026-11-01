<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\AcademicDataController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\DashboardController;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    // Registrasi mahasiswa
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/khs', [KhsController::class, 'create'])->name('khs.create');
    Route::post('/khs', [KhsController::class, 'store'])->name('khs.store');
    Route::delete('/khs', [KhsController::class, 'destroy'])->name('khs.destroy');

    Route::get('/academic', [AcademicDataController::class, 'index'])->name('academic.index');
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
    Route::post('/recommendations/recalculate', [SpkController::class, 'recalculate'])
        ->name('recommendations.recalculate');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
