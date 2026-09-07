<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DomainCatalogueController;
use App\Http\Controllers\DomainObservationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::prefix('domain')->group(function () {
        Route::get('/indicators', [DomainCatalogueController::class, 'index']);
        Route::get('/observations', [DomainObservationController::class, 'index']);
        Route::post('/manual-observations', [DomainObservationController::class, 'store']);
        Route::post('/observations/{observation}/revisions', [DomainObservationController::class, 'revise']);
        Route::post('/revisions/{revision}/submit', [DomainObservationController::class, 'submit']);
        Route::post('/revisions/{revision}/publish', [DomainObservationController::class, 'publish']);
        Route::post('/revisions/{revision}/reject', [DomainObservationController::class, 'reject']);
        Route::get('/observations/{observation}/history', [DomainObservationController::class, 'history']);
    });
});
