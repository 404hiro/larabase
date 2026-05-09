<?php

use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('settings', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::redirect('settings/profile', '/settings');
    Route::redirect('settings/appearance', '/settings')->name('appearance.edit');
});
