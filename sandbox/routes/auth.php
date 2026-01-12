<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Note: Main auth routes (login, register, forgot-password, reset-password)
// are defined in routes/web.php with locale prefix.
// This file only contains routes that don't need localization.

Route::middleware('auth')->group(function () {
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
});
