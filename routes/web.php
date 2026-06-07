<?php

use App\Http\Controllers\Labs\LabActionController;
use App\Http\Controllers\Labs\LabDashboardController;
use App\Http\Controllers\Labs\LabResetController;
use App\Http\Controllers\Labs\LabStateController;
use App\Http\Controllers\LocaleSwitchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Route to switch locale
Route::get('/locale/{locale}', LocaleSwitchController::class)
    ->where('locale', '[a-zA-Z]{2}')
    ->name('locale.switch');

// Labs routes
Route::get('/', LabDashboardController::class)->name('dashboard');
Route::prefix('labs')->name('labs.')->group(function () {
    Route::get('/state/{scenario}', LabStateController::class)->name('state');

    Route::post('/action/{scenario}/{mode}', LabActionController::class)
        ->name('action');

    Route::post('/reset/{scenario}/{mode}', LabResetController::class)
        ->name('reset');

    Route::post('/reset/{scenario}', [LabResetController::class, 'resetAll'])
        ->name('reset-all');
});
