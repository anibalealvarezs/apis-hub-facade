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

            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(fn () => new \Illuminate\Support\HtmlString('
                <div class="w-full flex items-center justify-center" x-show="$store.sidebar.isOpen">
                    <img src="' . asset('images/branding/apishub-trans-620.webp') . '" class="h-10 w-auto" />
                </div>
                <div class="w-full flex items-center justify-center relative" x-show="!$store.sidebar.isOpen">
                    <img src="' . asset('images/branding/apishub-favicon-trans.webp') . '" class="h-8 w-auto" />
                    <button @click.prevent="$store.sidebar.open()" class="absolute top-1/2 -translate-y-1/2 ltr:left-full rtl:right-full ltr:ml-2 rtl:mr-2 flex items-center justify-center w-6 h-6 text-gray-400 hover:text-primary-500 bg-white hover:bg-gray-50 border border-gray-200 shadow-sm rounded-full transition-colors z-[60]">
                        <svg class="w-3 h-3 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
                <style>
                    aside.fi-sidebar > div.overflow-x-clip { overflow: visible !important; }
                    aside.fi-sidebar .fi-sidebar-header > div:first-child { display: flex !important; width: 100%; justify-content: center; }
                    aside.fi-sidebar .fi-sidebar-header > button.mx-auto { display: none !important; }
                </style>
            '))
            ->darkModeBrandLogo(fn () => new \Illuminate\Support\HtmlString('
                <div class="w-full flex items-center justify-center" x-show="$store.sidebar.isOpen">
                    <img src="' . asset('images/branding/apishub-trans-light-620.webp') . '" class="h-10 w-auto" />
                </div>
                <div class="w-full flex items-center justify-center relative" x-show="!$store.sidebar.isOpen">
                    <img src="' . asset('images/branding/apishub-favicon-light.webp') . '" class="h-8 w-auto" />
                    <button @click.prevent="$store.sidebar.open()" class="absolute top-1/2 -translate-y-1/2 ltr:left-full rtl:right-full ltr:ml-2 rtl:mr-2 flex items-center justify-center w-6 h-6 text-gray-500 hover:text-primary-500 bg-gray-900 hover:bg-gray-800 border border-gray-700 shadow-sm rounded-full transition-colors z-[60]">
                        <svg class="w-3 h-3 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            '))
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
            ->databaseNotifications()
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
