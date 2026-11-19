<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\AlbumPhotoController;
use App\Http\Controllers\GalleryPhotoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicGalleryController;
use App\Http\Controllers\SsoLogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('galeria', [PublicGalleryController::class, 'index'])->name('galeria.index');
Route::get('galeria/{album}', [PublicGalleryController::class, 'show'])->name('galeria.show');

Route::get('sso/logout', SsoLogoutController::class)->name('sso.logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('gallery', GalleryPhotoController::class)
        ->only(['index', 'destroy'])
        ->parameters(['gallery' => 'galleryPhoto']);

    Route::resource('albums', AlbumController::class)
        ->only(['index', 'store', 'show', 'update']);

    Route::post('albums/{album}/photos', [AlbumPhotoController::class, 'store'])
        ->name('albums.photos.store');

    Route::patch('albums/{album}/photos/{galleryPhoto}', [AlbumPhotoController::class, 'update'])
        ->name('albums.photos.update');
});

require __DIR__.'/settings.php';
