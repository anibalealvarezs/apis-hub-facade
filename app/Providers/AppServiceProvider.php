<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        }

        \Illuminate\Support\Facades\Blade::component('oauth-buttons', \App\View\Components\OAuthButtons::class);

        // ─── Listener para el evento de REGISTRO DE FILAMENT ───
        // Filament dispara Filament\Events\Auth\Registered (NO Illuminate\Auth\Events\Registered)
        \Illuminate\Support\Facades\Event::listen(\Filament\Events\Auth\Registered::class, function (\Filament\Events\Auth\Registered $event) {
            $user = $event->getUser();

            // 1. Notificación visual al usuario en pantalla
            try {
                \Filament\Notifications\Notification::make()
                    ->title('Check your email inbox')
                    ->body('We have sent a verification link to your email to complete your registration.')
                    ->persistent()
                    ->info()
                    ->send();
            } catch (\Throwable $e) {}

            // 2. Correo al admin(s) via queue (fire-and-forget, no bloquea el response)
            try {
                $adminEmails = \App\Models\User::where('is_admin', true)
                    ->pluck('email')
                    ->all();

                if (!empty($adminEmails)) {
                    \Illuminate\Support\Facades\Mail::to($adminEmails)
                        ->queue(new \App\Mail\AdminRegistrationAlert($user->name, $user->email));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to queue admin registration alert', [
                    'user' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, \App\Listeners\SetSessionStartTime::class);

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Verified::class, function (\Illuminate\Auth\Events\Verified $event) {
            \Filament\Notifications\Notification::make()
                ->title('Email address verified')
                ->body('Welcome to APIs Hub! Your email has been successfully verified.')
                ->success()
                ->send();
        });
    }
}
