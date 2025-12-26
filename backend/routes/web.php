<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchemaController;
use App\Http\Controllers\DataGenerationController;

// Page Routes
Route::get('/', [SchemaController::class, 'index'])->name('generator.index');
Route::get('/configure', [SchemaController::class, 'show'])->name('generator.configure');

// Action Routes
Route::post('/schema', [SchemaController::class, 'store'])->name('schema.store');
Route::post('/generate', [DataGenerationController::class, 'store'])->name('generate.store');
Route::get('/jobs/{job_id}', [DataGenerationController::class, 'show'])->name('generate.job');
Route::get('/download/{file_name}', [DataGenerationController::class, 'download'])->name('generate.download');
