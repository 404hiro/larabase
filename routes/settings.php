<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\StripeConnectController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('settings', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('settings/stripe-connect/onboarding', [StripeConnectController::class, 'onboarding'])
        ->name('stripe-connect.onboarding');
    Route::get('settings/stripe-connect/refresh', [StripeConnectController::class, 'refresh'])
        ->name('stripe-connect.refresh');
    Route::get('settings/stripe-connect/return', [StripeConnectController::class, 'return'])
        ->name('stripe-connect.return');

    Route::redirect('settings/profile', '/settings');
    Route::redirect('settings/appearance', '/settings');
});
