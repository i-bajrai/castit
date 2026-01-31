<?php

use App\Http\Controllers\BudgetAdjustmentController;
use App\Http\Controllers\ControlAccountController;
use App\Http\Controllers\CostPackageController;
use App\Http\Controllers\LineItemController;
use App\Http\Controllers\LineItemForecastController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'index'])->name('dashboard');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects-trash', [ProjectController::class, 'trash'])->name('projects.trash');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore')->withTrashed();
    Route::delete('/projects/{project}/force-delete', [ProjectController::class, 'forceDelete'])->name('projects.force-delete')->withTrashed();
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/reports', [ProjectController::class, 'reports'])->name('projects.reports');
    Route::get('/projects/{project}/executive-summary', [ProjectController::class, 'executiveSummary'])->name('projects.executive-summary');

    Route::get('/projects/{project}/setup', [ProjectController::class, 'setup'])->name('projects.setup');
    Route::get('/projects/{project}/budget', [ProjectController::class, 'budget'])->name('projects.budget');
    Route::post('/projects/{project}/budget', [ProjectController::class, 'storeBudget'])->name('projects.budget.store');

    Route::get('/projects/{project}/settings', [ProjectSettingsController::class, 'index'])->name('projects.settings');

    Route::post('/projects/{project}/control-accounts/bulk', [ControlAccountController::class, 'bulkStore'])->name('projects.control-accounts.bulk-store');
    Route::post('/projects/{project}/control-accounts', [ControlAccountController::class, 'store'])->name('projects.control-accounts.store');
    Route::put('/projects/{project}/control-accounts/{controlAccount}', [ControlAccountController::class, 'update'])->name('projects.control-accounts.update');
    Route::delete('/projects/{project}/control-accounts/{controlAccount}', [ControlAccountController::class, 'destroy'])->name('projects.control-accounts.destroy');

    Route::post('/projects/{project}/budget-adjustments', [BudgetAdjustmentController::class, 'store'])->name('projects.budget-adjustments.store');

    Route::post('/projects/{project}/cost-packages', [CostPackageController::class, 'store'])->name('projects.cost-packages.store');
    Route::put('/projects/{project}/cost-packages/{costPackage}', [CostPackageController::class, 'update'])->name('projects.cost-packages.update');
    Route::delete('/projects/{project}/cost-packages/{costPackage}', [CostPackageController::class, 'destroy'])->name('projects.cost-packages.destroy');

    Route::post('/projects/{project}/cost-packages/{costPackage}/line-items', [LineItemController::class, 'store'])->name('projects.line-items.store');
    Route::put('/projects/{project}/cost-packages/{costPackage}/line-items/{lineItem}', [LineItemController::class, 'update'])->name('projects.line-items.update');
    Route::delete('/projects/{project}/cost-packages/{costPackage}/line-items/{lineItem}', [LineItemController::class, 'destroy'])->name('projects.line-items.destroy');

    Route::post('/projects/{project}/data-entry/line-items', [LineItemForecastController::class, 'store'])->name('projects.data-entry.line-items.store');
    Route::patch('/projects/{project}/forecasts/{forecast}/ctd-qty', [LineItemForecastController::class, 'updateCtdQty'])->name('projects.forecasts.update-ctd-qty');
    Route::patch('/projects/{project}/forecasts/{forecast}/comment', [LineItemForecastController::class, 'updateComment'])->name('projects.forecasts.update-comment');
    Route::post('/projects/{project}/forecasts/import', [LineItemForecastController::class, 'import'])->name('projects.forecasts.import');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
