<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Gate;
use App\Models\Dashboard;
use App\Policies\DashboardPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Domain\ChannelProfiles\ChannelProfileRegistry::class, function ($app) {
            $registry = new \App\Domain\ChannelProfiles\ChannelProfileRegistry();
            $registry->register(new \App\Domain\ChannelProfiles\Profiles\GoogleSearchConsoleProfile());
            $registry->register(new \App\Domain\ChannelProfiles\Profiles\FacebookMarketingProfile());
            $registry->register(new \App\Domain\ChannelProfiles\Profiles\FacebookOrganicProfile());
            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Diagnostic: verify boot() runs
        error_log('[LIVEWIRE_DEBUG] AppServiceProvider::boot() started');
        \Illuminate\Support\Facades\Log::warning('[LIVEWIRE_DEBUG] AppServiceProvider::boot() logging test');

        // Wrap Livewire update handler to log TypeErrors before Livewire swallows them
        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            return \Illuminate\Support\Facades\Route::post('/livewire/update', function (\Illuminate\Http\Request $request) use ($handle) {
                // Temporarily enable debug so Livewire re-throws the TypeError instead of
                // silently calling abort(419). We catch it, log the full details, then
                // restore the 419 ourselves.
                $originalDebug = config('app.debug');
                config(['app.debug' => true]);
                try {
                    [$class, $method] = $handle;
                    return app($class)->{$method}($request);
                } catch (\TypeError $e) {
                    \Illuminate\Support\Facades\Log::error('Livewire TypeError in update', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    abort(419);
                } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                    // If we got an HttpException despite debug mode, log it and re-throw
                    \Illuminate\Support\Facades\Log::warning('Livewire HttpException despite debug mode', [
                        'status' => $e->getStatusCode(),
                    ]);
                    throw $e;
                } finally {
                    config(['app.debug' => $originalDebug]);
                }
            })->middleware('web');
        });

        Gate::policy(Dashboard::class, DashboardPolicy::class);

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['en', 'es'])
                ->visible(outsidePanels: true);
        });



        \Livewire\Livewire::component('personal_info', \App\Livewire\CustomPersonalInfo::class);

        \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\BillingProfile::class);
        \Laravel\Cashier\Cashier::useSubscriptionModel(\App\Models\Subscription::class);
        \Laravel\Cashier\Cashier::useSubscriptionItemModel(\App\Models\SubscriptionItem::class);

        \App\Models\SubscriptionPlan::observe(\App\Observers\SubscriptionPlanObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        \App\Models\BillingProfile::observe(\App\Observers\BillingProfileObserver::class);

        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        }

        \Illuminate\Support\Facades\Blade::component('oauth-buttons', \App\View\Components\OAuthButtons::class);

        // ─── Listener para el evento de REGISTRO DE FILAMENT ───
        // Filament dispara Filament\Events\Auth\Registered (NO Illuminate\Auth\Events\Registered)
        // NOTA: Este listener se ejecuta ANTES de Filament::auth()->login(), por lo que
        // NO se debe manipular la sesión aquí (ej. Notification::send()). Filament ya
        // muestra su propia página de verificación de email tras el registro.
        \Illuminate\Support\Facades\Event::listen(\Filament\Events\Auth\Registered::class, function (\Filament\Events\Auth\Registered $event) {
            /** @var \App\Models\User $user */
            $user = $event->getUser();

            // Correo al admin(s) via queue (fire-and-forget, no bloquea el response)
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

        // Invitations
        \Illuminate\Support\Facades\Event::listen(\Filament\Events\Auth\Registered::class, \App\Listeners\ProcessProjectInvitation::class);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, \App\Listeners\ProcessProjectInvitation::class);
        
        // Stripe Webhooks
        \Illuminate\Support\Facades\Event::listen(\Laravel\Cashier\Events\WebhookReceived::class, \App\Listeners\StripeWebhookListener::class);

        // ─── Notificación visual tras verificación de email ───
        // Se dispara cuando el usuario hace click en el link de verificación.
        // Usa session push para que Filament la muestre en la página de login.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Verified::class, function (\Illuminate\Auth\Events\Verified $event) {
            \Filament\Notifications\Notification::make()
                ->title('✅ Email verified successfully')
                ->body('Your email has been confirmed. You can now log in to APIs Hub.')
                ->success()
                ->persistent()
                ->send();
        });

    }
}
