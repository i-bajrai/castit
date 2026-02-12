<?php

use App\Http\Controllers\CompanyMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/members', [CompanyMemberController::class, 'index'])->name('members.index');
Route::post('/members', [CompanyMemberController::class, 'store'])->name('members.store');
Route::put('/members/{user}', [CompanyMemberController::class, 'update'])->name('members.update');
Route::delete('/members/{user}', [CompanyMemberController::class, 'destroy'])->name('members.destroy');
Route::post('/members/{user}/restore', [CompanyMemberController::class, 'restore'])->name('members.restore');
