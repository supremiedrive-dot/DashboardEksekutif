<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebManualEntryController;
use App\Http\Controllers\WebReviewController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'show'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('web.login');
});
Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('web.logout');
    Route::get('/data-entry', [WebManualEntryController::class, 'index'])->name('data-entry');
    Route::post('/data-entry', [WebManualEntryController::class, 'store'])->name('data-entry.store');
    Route::post('/data-entry/revisions/{revision}/submit', [WebManualEntryController::class, 'submit'])->name('data-entry.submit');
    Route::get('/review', [WebReviewController::class, 'index'])->name('review');
    Route::post('/review/revisions/{revision}/publish', [WebReviewController::class, 'publish'])->name('review.publish');
    Route::post('/review/revisions/{revision}/reject', [WebReviewController::class, 'reject'])->name('review.reject');
});
