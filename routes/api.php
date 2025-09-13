<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetsController;

Route::get('/assets', [AssetsController::class, 'index']);
Route::get('/assets/{id}', [AssetsController::class, 'show']);
