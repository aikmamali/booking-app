<?php

declare(strict_types=1);

use App\Livewire\Home;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');

// Customer area (PWA). Staff use the Filament panel at /admin.
Route::prefix('app')->name('app.')->group(function (): void {
    Route::get('/', Home::class)->name('home');
});
