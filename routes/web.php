<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('welcome');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard', [
        'linksCount' => request()->user()->links()->count(),
        'userName' => request()->user()->name,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('links', [LinkController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('links.index');

Route::post('links', [LinkController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('links.store');

Route::put('links/{link}', [LinkController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('links.update');

Route::post('links/{link}/widgets', [\App\Http\Controllers\WidgetController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('widgets.store');

Route::post('links/{link}/widgets/sync', [\App\Http\Controllers\WidgetController::class, 'sync'])
    ->middleware(['auth', 'verified'])
    ->name('widgets.sync');

Route::post('widgets/upload-image', [\App\Http\Controllers\WidgetController::class, 'uploadImage'])
    ->middleware(['auth', 'verified'])
    ->name('widgets.upload-image');

Route::post('fetch-ogp', [\App\Http\Controllers\WidgetController::class, 'fetchOgp'])
    ->middleware(['auth', 'verified'])
    ->name('fetch-ogp');

// 管理者ルート
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
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
});

// 設定ルート
require __DIR__.'/settings.php';

Route::get('/@{link:slug}/support', [LinkController::class, 'support'])->name('links.support');
Route::get('/@{link:slug}', [LinkController::class, 'show'])->name('links.show');
