<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Filament\Resources\SubscriptionPlanResource\RelationManagers;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('tier')
                        ->options([
                            'free' => 'Free',
                            'pro' => 'Pro',
                            'ultra' => 'Ultra',
                            'enterprise' => 'Enterprise',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                ])->columns(2),

                Forms\Components\Section::make('Pricing & Gateways')->schema([
                    Forms\Components\TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->default(0.00)
                        ->prefix('$'),
                    Forms\Components\TextInput::make('currency')
                        ->required()
                        ->maxLength(3)
                        ->default('USD'),
                    Forms\Components\Select::make('billing_cycle')
                        ->options([
                            'monthly' => 'Monthly',
                            'yearly' => 'Yearly',
                        ])
                        ->required()
                        ->default('monthly'),
                    Forms\Components\TextInput::make('stripe_price_id')
                        ->maxLength(255)
                        ->label('Stripe Price ID'),
                    Forms\Components\TextInput::make('paypal_plan_id')
                        ->maxLength(255)
                        ->label('PayPal Plan ID'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tier')
                    ->badge(),
                Tables\Columns\TextColumn::make('price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_cycle'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
