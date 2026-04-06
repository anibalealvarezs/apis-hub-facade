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

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Registered::class, function (\Illuminate\Auth\Events\Registered $event) {
            \Filament\Notifications\Notification::make()
                ->title('Check your email inbox')
                ->body('We have sent a verification link to your email to complete your registration.')
                ->persistent()
                ->info()
                ->send();

            // Send Administrative Alert
            try {
                \Illuminate\Support\Facades\Mail::to('anibalealvarezs@gmail.com')
                    ->send(new \App\Mail\AdminRegistrationAlert($event->user));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Admin registration alert failed', ['error' => $e->getMessage()]);
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
