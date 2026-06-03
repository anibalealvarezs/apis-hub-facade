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
    
    // PayPal Checkout Routes
    Route::post('paypal/checkout', [App\Http\Controllers\PayPalCheckoutController::class, 'checkout'])->name('paypal.checkout');
    Route::get('paypal/return', [App\Http\Controllers\PayPalCheckoutController::class, 'return'])->name('paypal.return');
    
    // Stripe Checkout Routes
    Route::post('stripe/checkout', [App\Http\Controllers\StripeCheckoutController::class, 'checkout'])->name('stripe.checkout');
    Route::get('stripe/return', [App\Http\Controllers\StripeCheckoutController::class, 'return'])->name('stripe.return');
});

// Webhooks
Route::post('/webhooks/paypal', [App\Http\Controllers\PayPalWebhookController::class, 'handle'])->name('webhooks.paypal');

// Invitations (Project Collaboration)
Route::middleware(['web'])->group(function () {
    Route::get('/app/invitations/{token}/accept', [\App\Http\Controllers\InvitationController::class, 'accept'])->name('invitations.accept');
    Route::get('/app/billing-invitations/{token}/accept', [\App\Http\Controllers\BillingInvitationController::class, 'accept'])->name('billing-invitations.accept');
    Route::get('/app/transfers/{token}/review', [\App\Http\Controllers\TransferController::class, 'review'])->name('transfers.review');
    Route::post('/app/transfers/{token}/process', [\App\Http\Controllers\TransferController::class, 'process'])->name('transfers.process');
    Route::post('/app/transfers/{token}/reject', [\App\Http\Controllers\TransferController::class, 'reject'])->name('transfers.reject');
    
    // Pending Email Verification
    Route::get('/profile/verify-email/{id}/{hash}', function (\Illuminate\Http\Request $request, $id, $hash) {
        $user = \App\Models\User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->pending_email))) {
            abort(403, 'Invalid signature.');
        }

        $user->update([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('filament.app.pages.dashboard')->with('status', 'Email updated successfully!');
    })->middleware(['signed'])->name('profile.verify-pending-email');
});

// FB Deauthorize Callback (Public POST)
Route::post('social/{provider}/deauthorize', [App\Http\Controllers\OAuthController::class, 'handleDeauthorize'])->name('social.deauthorize');
Route::post('social/{provider}/delete-data', [App\Http\Controllers\OAuthController::class, 'handleDataDeletion'])->name('social.delete-data');

Route::get('/caddy/check', [\App\Http\Controllers\CaddyController::class, 'check']);

Route::post('/api/heartbeat', [\App\Http\Controllers\MonitoringController::class, 'heartbeat']);
Route::post('/api/channels/auth-failed', [\App\Http\Controllers\MonitoringController::class, 'authFailed']);
Route::post('/api/token-authority/refresh', [\App\Http\Controllers\TokenAuthorityController::class, 'refresh']);
Route::post('/api/gsc/summary', [\App\Http\Controllers\Api\GoogleSearchConsoleController::class, 'summary'])->middleware(['web', 'auth']);
Route::post('/api/gsc/chart', [\App\Http\Controllers\Api\GoogleSearchConsoleController::class, 'chart'])->middleware(['web', 'auth']);
Route::post('/api/gsc/table', [\App\Http\Controllers\Api\GoogleSearchConsoleController::class, 'table'])->middleware(['web', 'auth']);

Route::post('/api/fbm/summary', [\App\Http\Controllers\Api\FacebookMarketingController::class, 'summary'])->middleware(['web', 'auth']);
Route::post('/api/fbm/chart', [\App\Http\Controllers\Api\FacebookMarketingController::class, 'chart'])->middleware(['web', 'auth']);
Route::post('/api/fbm/table', [\App\Http\Controllers\Api\FacebookMarketingController::class, 'table'])->middleware(['web', 'auth']);

Route::post('/api/fbo/summary', [\App\Http\Controllers\Api\FacebookOrganicController::class, 'summary'])->middleware(['web', 'auth']);
Route::post('/api/fbo/chart', [\App\Http\Controllers\Api\FacebookOrganicController::class, 'chart'])->middleware(['web', 'auth']);
Route::post('/api/fbo/table', [\App\Http\Controllers\Api\FacebookOrganicController::class, 'table'])->middleware(['web', 'auth']);
Route::post('/api/fbo/post', [\App\Http\Controllers\Api\FacebookOrganicController::class, 'post'])->middleware(['web', 'auth']);

Route::get('/login', fn () => redirect()->route('filament.app.auth.login'))->name('login');

// Legal Documents
Route::get('/privacy', [\App\Http\Controllers\LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/tos', [\App\Http\Controllers\LegalController::class, 'tos'])->name('legal.tos');
Route::get('/data-deletion', [\App\Http\Controllers\LegalController::class, 'dataDeletion'])->name('legal.data-deletion');
