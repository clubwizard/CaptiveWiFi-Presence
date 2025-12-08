<?php

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

// Health check endpoint for DigitalOcean App Platform
Route::get('/health', HealthCheckController::class)->name('health');

Route::get('/', function () {
    return view('welcome');
});
