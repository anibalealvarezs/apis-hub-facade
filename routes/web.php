<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-saas', function () {
    return 'Server is responding!';
});

Route::get('/', [\App\Http\Controllers\LandingController::class, 'index']);
Route::post('/subscribe', [\App\Http\Controllers\LandingController::class, 'subscribe'])->name('landing.subscribe');
Route::get('/unsubscribe', [\App\Http\Controllers\LandingController::class, 'unsubscribe'])->name('landing.unsubscribe')->middleware('signed');

// APIs Hub SaaS: OAuth Hub for Tenants
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('connect/social/{provider}', [App\Http\Controllers\OAuthController::class, 'redirect'])->name('app.social-login');
    Route::get('connect/{tenant}/{provider}', [App\Http\Controllers\OAuthController::class, 'redirect'])->name('app.connect');
    Route::get('connect/{tenant}/{provider}/callback', [App\Http\Controllers\OAuthController::class, 'callback'])->name('app.connect.callback');
});

Route::get('/caddy/check', [\App\Http\Controllers\CaddyController::class, 'check']);

Route::post('/api/heartbeat', [\App\Http\Controllers\MonitoringController::class, 'heartbeat']);

Route::get('/login', fn () => redirect()->route('filament.app.auth.login'))->name('login');
