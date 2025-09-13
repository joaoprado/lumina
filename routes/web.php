<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/favorites', function () {
    return Inertia::render('Favorites');
})->name('favorites.index');

Route::get('/assets/{id}', function (string $id) {
    return Inertia::render('AssetDetails', ['id' => $id]);
})->name('assets.show');
