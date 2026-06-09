<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingProfileResource\Pages\ListBillingProfiles;
use App\Filament\Resources\BillingProfileResource\Pages\ViewBillingProfile;
use App\Models\BillingProfile;
use App\Services\BillingLifecycleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BillingProfileResource extends Resource
{
    protected static ?string $model = BillingProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Infrastructure';

    protected static ?string $navigationLabel = 'Billing Profiles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Info')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled(),
                        Forms\Components\TextInput::make('reference_name')
                            ->label('Reference Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('tier')
                            ->disabled()
                            ->formatStateUsing(fn (\App\Enums\UserTier $state): string => $state->getLabel()),
                        Forms\Components\TextInput::make('status')
                            ->disabled(),
                        Forms\Components\Toggle::make('is_default')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Owner')
                    ->schema([
                        Forms\Components\Placeholder::make('owner_name')
                            ->label('Name')
                            ->content(fn (BillingProfile $record): ?string => $record->user?->name),
                        Forms\Components\Placeholder::make('owner_email')
                            ->label('Email')
                            ->content(fn (BillingProfile $record): ?string => $record->user?->email),
                    ])->columns(2),

                Forms\Components\Section::make('Billing Cycle')
                    ->schema([
                        Forms\Components\Placeholder::make('current_cycle_starts_at')
                            ->label('Cycle Start')
                            ->content(fn (BillingProfile $record): ?string => $record->current_cycle_starts_at?->format('Y-m-d H:i:s')),
                        Forms\Components\Placeholder::make('current_cycle_ends_at')
                            ->label('Cycle End')
                            ->content(fn (BillingProfile $record): ?string => $record->current_cycle_ends_at?->format('Y-m-d H:i:s')),
                    ])->columns(2),

                Forms\Components\Section::make('Payment Method')
                    ->schema([
                        Forms\Components\Placeholder::make('payment_card')
                            ->label('Card')
                            ->content(fn (BillingProfile $record): string => $record->pm_type
                                ? ucfirst($record->pm_type) . ' (****' . ($record->pm_last_four ?? '') . ')'
                                : '—'),
                        Forms\Components\Placeholder::make('paypal_email')
                            ->label('PayPal Email')
                            ->content(fn (BillingProfile $record): ?string => $record->paypal_email),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Owner Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('tier')
                    ->badge()
                    ->color(fn (\App\Enums\UserTier $state): string => match ($state) {
                        \App\Enums\UserTier::FREE => 'gray',
                        \App\Enums\UserTier::PRO => 'success',
                        \App\Enums\UserTier::ULTRA => 'warning',
                        \App\Enums\UserTier::FOUNDER => 'info',
                        \App\Enums\UserTier::ENTERPRISE => 'danger',
                        \App\Enums\UserTier::SUSPENDED => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'past_due' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Projects')
                    ->counts('projects')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quota')
                    ->label('Quota')
                    ->state(function (BillingProfile $record): string {
                        $service = app(BillingLifecycleService::class);
                        $max = $service->getMaxProjectsForTier($record->tier);
                        $count = $record->projects()->count();
                        return "{$count} / {$max}";
                    }),
                Tables\Columns\TextColumn::make('current_cycle_starts_at')
                    ->label('Cycle Start')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_cycle_ends_at')
                    ->label('Cycle End')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('pm_type')
                    ->label('Payment')
                    ->formatStateUsing(fn (?string $state, BillingProfile $record): string => $record->pm_type
                        ? ucfirst($record->pm_type) . ' (****' . ($record->pm_last_four ?? '') . ')'
                        : ($record->paypal_email ? 'PayPal' : '—'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options(fn () => \App\Enums\UserTier::class),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\BillingProfileResource\RelationManagers\ProjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingProfiles::route('/'),
            'view' => ViewBillingProfile::route('/{record}'),
        ];
    }
}
