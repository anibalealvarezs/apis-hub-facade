<?php

namespace App\Filament\Pages;

use App\Settings\PaymentSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManagePaymentSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Configuración del Sistema';
    protected static ?string $navigationLabel = 'Métodos de Pago';
    protected static ?string $title = 'Configurar Métodos de Pago';

    protected static string $settings = PaymentSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pasarelas Habilitadas en Checkout')
                    ->description(__('Activa o desactiva las opciones de pago que verán los usuarios al momento de suscribirse.'))
                    ->schema([
                        Forms\Components\Toggle::make('enable_stripe')
                            ->label(__('Habilitar Stripe (Tarjetas de Crédito)'))
                            ->helperText(__('Permitir pagos con tarjeta de crédito/débito a través de Stripe.')),
                        
                        Forms\Components\Toggle::make('enable_paypal')
                            ->label(__('Habilitar PayPal'))
                            ->helperText(__('Permitir pagos utilizando el balance de PayPal o cuentas asociadas.')),
                    ]),
            ]);
    }
}
