<?php

use App\Http\Controllers\Ads\AdWizardController;
use App\Http\Controllers\Ads\CampaignController;
use App\Http\Controllers\Ads\CampaignReviewController;
use App\Http\Controllers\Ads\CreativeMediaController;
use App\Http\Controllers\Ads\MetaCampaignPublishingController;
use App\Http\Controllers\Ads\MetaCampaignStatusController;
use App\Http\Controllers\BusinessOnboardingController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\BusinessSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetaAssetSelectionController;
use App\Http\Controllers\MetaConnectionController;
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

        Route::get('/meta-connection', [MetaConnectionController::class, 'index'])->name('meta-connection.index');
        Route::get('/meta-connection/redirect', [MetaConnectionController::class, 'redirect'])->name('meta-connection.redirect');
        Route::get('/meta-connection/callback', [MetaConnectionController::class, 'callback'])->name('meta-connection.callback');
        Route::post('/meta-connection/sync', [MetaConnectionController::class, 'sync'])->name('meta-connection.sync');
        Route::put('/meta-connection/assets', [MetaAssetSelectionController::class, 'update'])->name('meta-connection.assets.update');
        Route::delete('/meta-connection', [MetaConnectionController::class, 'disconnect'])->name('meta-connection.disconnect');
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
        Route::post('/campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
        Route::get('/campaigns/{campaign}/wizard/goal', [AdWizardController::class, 'editGoal'])->name('campaigns.wizard.goal.edit');
        Route::put('/campaigns/{campaign}/wizard/goal', [AdWizardController::class, 'updateGoal'])->name('campaigns.wizard.goal.update');
        Route::get('/campaigns/{campaign}/wizard/assets', [AdWizardController::class, 'editAssets'])->name('campaigns.wizard.assets.edit');
        Route::put('/campaigns/{campaign}/wizard/assets', [AdWizardController::class, 'updateAssets'])->name('campaigns.wizard.assets.update');
        Route::get('/campaigns/{campaign}/wizard/creative', [AdWizardController::class, 'editCreative'])->name('campaigns.wizard.creative.edit');
        Route::put('/campaigns/{campaign}/wizard/creative', [AdWizardController::class, 'updateCreative'])->name('campaigns.wizard.creative.update');
        Route::get('/campaigns/{campaign}/wizard/audience', [AdWizardController::class, 'editAudience'])->name('campaigns.wizard.audience.edit');
        Route::put('/campaigns/{campaign}/wizard/audience', [AdWizardController::class, 'updateAudience'])->name('campaigns.wizard.audience.update');
        Route::get('/campaigns/{campaign}/wizard/budget', [AdWizardController::class, 'editBudget'])->name('campaigns.wizard.budget.edit');
        Route::put('/campaigns/{campaign}/wizard/budget', [AdWizardController::class, 'updateBudget'])->name('campaigns.wizard.budget.update');
        Route::get('/campaigns/{campaign}/review', [CampaignReviewController::class, 'show'])->name('campaigns.review');
        Route::post('/campaigns/{campaign}/mark-ready', [CampaignReviewController::class, 'markReady'])->name('campaigns.mark-ready');
        Route::get('/campaigns/{campaign}/publish', [MetaCampaignPublishingController::class, 'confirm'])->name('campaigns.publish.confirm');
        Route::post('/campaigns/{campaign}/publish', [MetaCampaignPublishingController::class, 'publish'])->middleware(['password.confirm', 'throttle:5,1'])->name('campaigns.publish');
        Route::get('/campaigns/{campaign}/publish/progress', [MetaCampaignPublishingController::class, 'progress'])->name('campaigns.publish.progress');
        Route::get('/campaigns/{campaign}/publish/status', [MetaCampaignPublishingController::class, 'status'])->middleware('throttle:60,1')->name('campaigns.publish.status');
        Route::post('/campaigns/{campaign}/publish/retry', [MetaCampaignPublishingController::class, 'retry'])->middleware(['password.confirm', 'throttle:5,1'])->name('campaigns.publish.retry');
        Route::post('/campaigns/{campaign}/activate', [MetaCampaignStatusController::class, 'activate'])->middleware(['password.confirm', 'throttle:10,1'])->name('campaigns.activate');
        Route::post('/campaigns/{campaign}/pause', [MetaCampaignStatusController::class, 'pause'])->middleware(['throttle:10,1'])->name('campaigns.pause');
        Route::get('/campaigns/{campaign}/media', CreativeMediaController::class)->name('campaigns.media.show');
        Route::get('/advertisements/create', fn () => redirect()->route('campaigns.create'))->name('advertisements.create');
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
