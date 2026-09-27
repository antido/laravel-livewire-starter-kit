<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::livewire('login', Login::class)->name('login');
    Route::livewire('register', Register::class)->middleware('registration')->name('register');
    Route::livewire('forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::livewire('verify-email', VerifyEmail::class)->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::livewire('confirm-password', ConfirmPassword::class)->middleware('active')->name('password.confirm');

    Route::post('logout', LogoutController::class)->name('logout');
});
