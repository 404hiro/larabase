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

Route::redirect('dashboard/messages', '/dashboard/messages/inbox')
    ->middleware(['auth']);

Route::get('dashboard/messages/{mailbox}', [\App\Http\Controllers\DashboardController::class, 'messages'])
    ->middleware(['auth'])
    ->whereIn('mailbox', ['inbox', 'sent'])
    ->name('dashboard.messages');

Route::get('dashboard/links/{link:id}', [LinkController::class, 'manage'])
    ->middleware(['auth'])
    ->name('dashboard.links.show');

Route::get('dashboard/links', [LinkController::class, 'index'])
    ->middleware(['auth'])
    ->name('links.index');

Route::post('dashboard/links', [LinkController::class, 'store'])
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

Route::get('/@{link:slug}/message', [LinkController::class, 'message'])->name('links.message');
Route::post('/@{link:slug}/messages', [\App\Http\Controllers\MessageController::class, 'store'])
    ->middleware(['auth'])
    ->name('messages.store');

Route::patch('/messages/{message}', [\App\Http\Controllers\MessageController::class, 'update'])
    ->middleware(['auth'])
    ->name('messages.update');

Route::delete('/messages/{message}', [\App\Http\Controllers\MessageController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('messages.destroy');

Route::get('/@{link:slug}/widgets/{widget}/click', [\App\Http\Controllers\WidgetClickController::class, 'click'])
    ->name('widgets.click');

Route::get('/@{link:slug}', [LinkController::class, 'show'])->name('links.show');
