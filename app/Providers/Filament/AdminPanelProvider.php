<?php

namespace App\Providers\Filament;

use App\Http\Middleware\VerifyReCaptcha;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
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
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->emailVerification()

            ->brandLogo(asset('images/branding/apishub-trans-620.webp'))
            ->darkModeBrandLogo(asset('images/branding/apishub-trans-light-620.webp'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/branding/apishub-favicon.webp'))
            ->colors([
                'primary' => '#00a7f9',
            ])
            ->font('Outfit')
            ->renderHook(
                'panels::styles.after',
                fn () => \Illuminate\Support\Facades\Blade::render('<link rel="stylesheet" href="{{ asset(\'css/branding.css\') }}">')
            )
            ->renderHook(
                'panels::head.start',
                fn () => \Illuminate\Support\Facades\Blade::render('<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">')
            )
            ->renderHook(
                'panels::auth.login.form.after',
                fn () => \Illuminate\Support\Facades\Blade::render("
                    <div id='recaptcha-script-container-admin'>
                        <script src='https://www.google.com/recaptcha/enterprise.js?render={{ config('services.recaptcha.site_key') }}'></script>
                        <script>
                            function injectAdminReCaptcha() {
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
                            document.addEventListener('DOMContentLoaded', injectAdminReCaptcha);
                            window.addEventListener('livewire:load', injectAdminReCaptcha);
                            setInterval(injectAdminReCaptcha, 60000);
                        </script>
                        <p class='text-xs text-gray-400 text-center mt-4'>Protected by Google reCAPTCHA v3</p>
                    </div>
                ")
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ])
            ->plugin(
                \Jeffgreco13\FilamentBreezy\BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: false,
                    )
                    ->enableTwoFactorAuthentication(
                        force: false,
                    )
            )
            ->plugin(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::make())
            ->plugin(
                \Filament\SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(['en', 'es'])
            )
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\CheckLogoutAt::class,
            ]);
    }
}
