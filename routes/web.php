<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TitleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('welcome');

Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('links', [LinkController::class, 'index'])
    ->middleware(['auth'])
    ->name('links.index');

Route::post('links', [LinkController::class, 'store'])
    ->middleware(['auth'])
    ->name('links.store');

Route::put('links/{link}', [LinkController::class, 'update'])
    ->middleware(['auth'])
    ->name('links.update');

Route::post('links/{link}/widgets', [\App\Http\Controllers\WidgetController::class, 'store'])
    ->middleware(['auth'])
    ->name('widgets.store');

Route::post('links/{link}/widgets/sync', [\App\Http\Controllers\WidgetController::class, 'sync'])
    ->middleware(['auth'])
    ->name('widgets.sync');

Route::post('widgets/upload-image', [\App\Http\Controllers\WidgetController::class, 'uploadImage'])
    ->middleware(['auth'])
    ->name('widgets.upload-image');

Route::post('fetch-ogp', [\App\Http\Controllers\WidgetController::class, 'fetchOgp'])
    ->middleware(['auth'])
    ->name('fetch-ogp');

// 管理者ルート
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('users', UserController::class)->names([
        'index' => 'admin.users.index',
        'show' => 'admin.users.show',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);

    Route::resource('titles', TitleController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names([
            'index' => 'admin.titles.index',
            'store' => 'admin.titles.store',
            'update' => 'admin.titles.update',
            'destroy' => 'admin.titles.destroy',
        ]);
});

// 設定ルート
require __DIR__.'/settings.php';

Route::get('/@{link:slug}/letter', [LinkController::class, 'letter'])->name('links.letter');
Route::get('/@{link:slug}', [LinkController::class, 'show'])->name('links.show');
