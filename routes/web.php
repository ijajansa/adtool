<?php

use App\Http\Controllers\BusinessOnboardingController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\BusinessSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware(['auth', 'verified', 'active-user'])->group(function () {
    Route::get('/business/onboarding', [BusinessOnboardingController::class, 'create'])
        ->name('business.onboarding.create');
    Route::post('/business/onboarding', [BusinessOnboardingController::class, 'store'])
        ->name('business.onboarding.store');
    Route::post('/businesses/{business}/switch', BusinessSwitchController::class)
        ->name('businesses.switch');

    Route::middleware('business-selected')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::view('/meta-connection', 'pages.placeholder', ['title' => 'Meta Connection'])->name('meta-connection.index');
        Route::view('/campaigns', 'pages.placeholder', ['title' => 'Campaigns'])->name('campaigns.index');
        Route::view('/advertisements/create', 'pages.placeholder', ['title' => 'Create Advertisement'])->name('advertisements.create');
        Route::view('/leads', 'pages.placeholder', ['title' => 'Leads'])->name('leads.index');
        Route::view('/reports', 'pages.placeholder', ['title' => 'Reports'])->name('reports.index');
        Route::view('/billing', 'pages.placeholder', ['title' => 'Billing'])->name('billing.index');

        Route::get('/settings/business', [BusinessSettingsController::class, 'edit'])->name('business.settings.edit');
        Route::put('/settings/business', [BusinessSettingsController::class, 'update'])->name('business.settings.update');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
