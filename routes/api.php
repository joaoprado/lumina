<?php

use App\Http\Controllers\AssetsController;
use App\Http\Controllers\FavoritesController;
use Illuminate\Support\Facades\Route;

Route::get('/assets', [AssetsController::class, 'index']);
Route::get('/assets/{id}', [AssetsController::class, 'show']);
Route::get('/assets/{id}/history', [AssetsController::class, 'history']);

// Favorites
Route::get('/favorites', [FavoritesController::class, 'index']);
Route::post('/favorites', [FavoritesController::class, 'store']);
Route::delete('/favorites/{assetId}', [FavoritesController::class, 'destroy']);
