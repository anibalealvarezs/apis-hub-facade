<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-saas', function () {
    return 'Server is responding!';
});

Route::get('/', [\App\Http\Controllers\LandingController::class, 'index']);
Route::post('/subscribe', [\App\Http\Controllers\LandingController::class, 'subscribe'])
    ->middleware(\App\Http\Middleware\VerifyReCaptcha::class)
    ->name('landing.subscribe');
Route::get('/unsubscribe', [\App\Http\Controllers\LandingController::class, 'unsubscribe'])->name('landing.unsubscribe')->middleware('signed');

// APIs Hub SaaS: OAuth Hub for Tenants
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('social/{provider}/redirect', [App\Http\Controllers\OAuthController::class, 'redirect'])->name('app.social-login');
    Route::get('social/{provider}/callback', [App\Http\Controllers\OAuthController::class, 'callback'])->name('app.social-callback');
    Route::get('connect/{tenant}/{provider}', [App\Http\Controllers\OAuthController::class, 'connect'])->name('app.connect');
});

// Invitations (Project Collaboration)
Route::middleware(['web'])->group(function () {
    Route::get('/app/invitations/{token}/accept', [\App\Http\Controllers\InvitationController::class, 'accept'])->name('invitations.accept');
    Route::get('/app/transfers/{token}/accept', [\App\Http\Controllers\TransferController::class, 'accept'])->name('transfers.accept');
});

// FB Deauthorize Callback (Public POST)
Route::post('social/{provider}/deauthorize', [App\Http\Controllers\OAuthController::class, 'handleDeauthorize'])->name('social.deauthorize');
Route::post('social/{provider}/delete-data', [App\Http\Controllers\OAuthController::class, 'handleDataDeletion'])->name('social.delete-data');

Route::get('/caddy/check', [\App\Http\Controllers\CaddyController::class, 'check']);

Route::post('/api/heartbeat', [\App\Http\Controllers\MonitoringController::class, 'heartbeat']);

Route::get('/login', fn () => redirect()->route('filament.app.auth.login'))->name('login');

// Legal Documents
Route::get('/privacy', [\App\Http\Controllers\LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/tos', [\App\Http\Controllers\LegalController::class, 'tos'])->name('legal.tos');
Route::get('/data-deletion', [\App\Http\Controllers\LegalController::class, 'dataDeletion'])->name('legal.data-deletion');
