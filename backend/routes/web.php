<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchemaController;
use App\Http\Controllers\DataGenerationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\SavedConfigController;

// Page Routes
Route::get('/', [SchemaController::class, 'index'])->name('generator.index');
Route::get('/configure', [SchemaController::class, 'show'])->name('generator.configure');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.store');
    Route::get('/password/forgot', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/password/forgot', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/configs', [SavedConfigController::class, 'index'])->name('configs.index');
    Route::post('/configs', [SavedConfigController::class, 'store'])->name('configs.store');
    Route::get('/configs/{config}', [SavedConfigController::class, 'show'])->name('configs.show');
});

// Action Routes
Route::post('/schema', [SchemaController::class, 'store'])->name('schema.store');
Route::post('/schema/connect', [SchemaController::class, 'storeFromConnection'])->name('schema.connect');
Route::post('/generate', [DataGenerationController::class, 'store'])->name('generate.store');
Route::get('/jobs/{job_id}', [DataGenerationController::class, 'show'])->name('generate.job');
Route::get('/download/{file_name}', [DataGenerationController::class, 'download'])->name('generate.download');
