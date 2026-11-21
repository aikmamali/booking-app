<?php

declare(strict_types=1);

use App\Livewire\Home;
use App\Modules\Identity\Http\Controllers\LogoutController;
use App\Modules\Identity\Http\Controllers\VerifyEmailController;
use App\Modules\Identity\Livewire\AcceptInvitation;
use App\Modules\Identity\Livewire\ForgotPassword;
use App\Modules\Identity\Livewire\Login;
use App\Modules\Identity\Livewire\Profile;
use App\Modules\Identity\Livewire\Register;
use App\Modules\Identity\Livewire\ResetPasswordForm;
use App\Modules\Identity\Livewire\VerifyEmailNotice;
use App\Modules\Membership\Livewire\MyMemberships;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');

// Customer area (PWA). Staff use the Filament panel at /admin.
Route::prefix('app')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', Login::class)->name('app.login');
        Route::get('register', Register::class)->name('app.register');
        Route::get('forgot-password', ForgotPassword::class)->name('app.password.request');
        // Route names below are the ones Laravel's notifications link to.
        Route::get('reset-password/{token}', ResetPasswordForm::class)->name('password.reset');
        Route::get('invitation/{token}', AcceptInvitation::class)->name('app.invitation');
    });

    Route::view('terms', 'legal.terms')->name('app.terms');
    Route::view('privacy', 'legal.privacy')->name('app.privacy');

    Route::middleware('auth')->group(function (): void {
        Route::post('logout', LogoutController::class)->name('app.logout');
        Route::get('verify-email', VerifyEmailNotice::class)->name('verification.notice');
        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::middleware('verified')->group(function (): void {
            Route::get('/', Home::class)->name('app.home');
            Route::get('profile', Profile::class)->name('app.profile');
            Route::get('membership', MyMemberships::class)->name('app.membership');
        });
    });
});
