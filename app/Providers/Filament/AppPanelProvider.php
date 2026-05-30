<?php

namespace App\Providers\Filament;

use App\Models\Project;
use App\Http\Middleware\VerifyReCaptcha;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateBatch;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            
            ->renderHook(
                \Filament\View\PanelsRenderHook::TOPBAR_START,
                fn () => view('filament.hooks.topbar-logo'),
            )

            ->profile()
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(fn () => new \Illuminate\Support\HtmlString('
                <div class="w-full flex items-center justify-center">
                    <img src="' . asset('images/branding/apishub-trans-620.webp') . '" class="h-10 w-auto" />
                </div>
            '))
            ->darkModeBrandLogo(fn () => new \Illuminate\Support\HtmlString('
                <div class="w-full flex items-center justify-center">
                    <img src="' . asset('images/branding/apishub-trans-light-620.webp') . '" class="h-10 w-auto" />
                </div>
            '))
            ->favicon(asset('images/branding/apishub-favicon.webp'))
            ->colors([
                'primary' => '#00a7f9',
            ])
            ->spa()
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->font('Outfit')
            ->renderHook(
                'panels::styles.after',
                fn () => \Illuminate\Support\Facades\Blade::render('<link rel="stylesheet" href="{{ asset(\'css/branding.css\') }}">')
            )
            ->renderHook(
                'panels::scripts.after',
                fn () => \Illuminate\Support\Facades\Blade::render('@vite([\'resources/js/filament-charts.js\'])')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::TENANT_MENU_AFTER,
                fn () => \Illuminate\Support\Facades\Blade::render('@livewire(\'global-infrastructure-status\')')
            )
            ->renderHook(
                'panels::head.start',
                fn () => \Illuminate\Support\Facades\Blade::render('<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">')
            )
            ->renderHook(
                'panels::auth.login.form.after',
                fn () => \Illuminate\Support\Facades\Blade::render("
                    <div id='recaptcha-script-container-login'>
                        <script src='https://www.google.com/recaptcha/enterprise.js?render={{ config('services.recaptcha.site_key') }}'></script>
                        <script>
                            function injectLoginReCaptcha() {
                                if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.enterprise !== 'undefined') {
                                    grecaptcha.enterprise.ready(function() {
                                        grecaptcha.enterprise.execute('{{ config('services.recaptcha.site_key') }}', {action: 'login'}).then(function(token) {
                                            let forms = document.querySelectorAll('form');
                                            forms.forEach(form => {
                                                let input = form.querySelector('input[name=\"recaptcha_token\"]');
                                                if (!input) {
                                                    input = document.createElement('input');
                                                    input.type = 'hidden';
                                                    input.name = 'recaptcha_token';
                                                    form.appendChild(input);
                                                }
                                                input.value = token;
                                                input.dispatchEvent(new Event('input', { bubbles: true }));
                                            });
                                        });
                                    });
                                }
                            }
                            document.addEventListener('DOMContentLoaded', injectLoginReCaptcha);
                            window.addEventListener('livewire:load', injectLoginReCaptcha);
                            setInterval(injectLoginReCaptcha, 60000);
                        </script>
                        <p class='text-xs text-gray-400 text-center mt-4'>Protected by Google reCAPTCHA v3</p>
                    </div>
                ")
            )
            ->renderHook(
                'panels::auth.register.form.after',
                fn () => \Illuminate\Support\Facades\Blade::render("
                    <div id='recaptcha-script-container-register'>
                        <script src='https://www.google.com/recaptcha/enterprise.js?render={{ config('services.recaptcha.site_key') }}'></script>
                        <script>
                            function injectRegisterReCaptcha() {
                                if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.enterprise !== 'undefined') {
                                    grecaptcha.enterprise.ready(function() {
                                        grecaptcha.enterprise.execute('{{ config('services.recaptcha.site_key') }}', {action: 'register'}).then(function(token) {
                                            let forms = document.querySelectorAll('form');
                                            forms.forEach(form => {
                                                let input = form.querySelector('input[name=\"recaptcha_token\"]');
                                                if (!input) {
                                                    input = document.createElement('input');
                                                    input.type = 'hidden';
                                                    input.name = 'recaptcha_token';
                                                    form.appendChild(input);
                                                }
                                                input.value = token;
                                                input.dispatchEvent(new Event('input', { bubbles: true }));
                                            });
                                        });
                                    });
                                }
                            }
                            document.addEventListener('DOMContentLoaded', injectRegisterReCaptcha);
                            window.addEventListener('livewire:load', injectRegisterReCaptcha);
                            setInterval(injectRegisterReCaptcha, 60000);
                        </script>
                        <p class='text-xs text-gray-400 text-center mt-4'>Protected by Google reCAPTCHA v3</p>
                    </div>
                ")
            )
            ->darkMode()
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->tenant(Project::class, slugAttribute: 'subdomain')
            ->tenantRegistration(\App\Filament\App\Pages\RegisterProject::class)
            ->tenantMiddleware([
                \App\Http\Middleware\ApplyTenantScopes::class,
            ], isPersistent: true)
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->discoverClusters(in: app_path('Filament/App/Clusters'), for: 'App\\Filament\\App\\Clusters')
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Exploration & Telemetry'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Data & Integrations'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Google'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Meta'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Administration'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Knowledge Base'),
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                VerifyReCaptcha::class,
                \App\Http\Middleware\SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureUserHasActiveProject::class,
                \App\Http\Middleware\CheckLogoutAt::class,
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('My Account')
                    ->url('/account')
                    ->icon('heroicon-o-user'),
            ])
            ->plugin(
                \Jeffgreco13\FilamentBreezy\BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: false, // Disable in app panel so it doesn't conflict with Account panel
                        shouldRegisterNavigation: false,
                        hasAvatars: false,
                    )
            )
            ->plugin(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::make());
    }
}
