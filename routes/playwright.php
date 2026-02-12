<?php

use App\Http\Controllers\PlaywrightController;
use Illuminate\Support\Facades\Route;

Route::post('/factory', [PlaywrightController::class, 'factory'])->name('playwright.factory');
Route::patch('/update', [PlaywrightController::class, 'update'])->name('playwright.update');
Route::post('/login', [PlaywrightController::class, 'login'])->name('playwright.login');
Route::post('/logout', [PlaywrightController::class, 'logout'])->name('playwright.logout');
Route::get('/csrf_token', [PlaywrightController::class, 'csrfToken'])->name('playwright.csrf-token');
