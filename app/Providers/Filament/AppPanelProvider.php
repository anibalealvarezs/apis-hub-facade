<?php

namespace App\Providers\Filament;

use App\Models\Project;
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
            ->id('app')
            ->path('app')
            ->login()
            ->registration()
            ->passwordReset()
            // ->emailVerification()
            ->profile()
            ->brandLogo(asset('images/branding/apishub-trans-620.png'))
            ->darkModeBrandLogo(asset('images/branding/apishub-trans-light-600.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/branding/apishub-favicon.png'))
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
            ->darkMode()
            ->tenant(Project::class, slugAttribute: 'subdomain')
            // ->tenantDomain('{tenant}.' . str_replace(['http://', 'https://'], '', config('app.url')))
            ->tenantRegistration(\App\Filament\App\Pages\RegisterProject::class)
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
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
            /* ->renderHook(
                'panels::auth.login.form.after',
                fn () => \Illuminate\Support\Facades\Blade::render('<x-oauth-buttons provider="facebook" />'),
            ) */;
        return $panel;
    }
}
