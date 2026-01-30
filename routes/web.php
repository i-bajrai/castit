<?php

use App\Http\Controllers\BudgetAdjustmentController;
use App\Http\Controllers\ControlAccountController;
use App\Http\Controllers\ForecastPeriodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/executive-summary', [ProjectController::class, 'executiveSummary'])->name('projects.executive-summary');

    Route::get('/projects/{project}/settings', [ProjectSettingsController::class, 'index'])->name('projects.settings');

    Route::post('/projects/{project}/control-accounts', [ControlAccountController::class, 'store'])->name('projects.control-accounts.store');
    Route::put('/projects/{project}/control-accounts/{controlAccount}', [ControlAccountController::class, 'update'])->name('projects.control-accounts.update');
    Route::delete('/projects/{project}/control-accounts/{controlAccount}', [ControlAccountController::class, 'destroy'])->name('projects.control-accounts.destroy');

    Route::post('/projects/{project}/periods', [ForecastPeriodController::class, 'store'])->name('projects.periods.store');
    Route::patch('/projects/{project}/periods/{period}/lock', [ForecastPeriodController::class, 'lock'])->name('projects.periods.lock');

    Route::post('/projects/{project}/budget-adjustments', [BudgetAdjustmentController::class, 'store'])->name('projects.budget-adjustments.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
