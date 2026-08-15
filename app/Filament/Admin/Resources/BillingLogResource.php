<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BillingLogResource\Pages;
use App\Models\BillingLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BillingLogResource extends Resource
{
    protected static ?string $model = BillingLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Financials');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Log Details')
                    ->schema([
                        Forms\Components\TextInput::make('event_type')
                            ->disabled(),
                        Forms\Components\TextInput::make('gateway')
                            ->disabled(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->disabled(),
                        Forms\Components\Select::make('billing_profile_id')
                            ->relationship('billingProfile', 'name')
                            ->disabled(),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('metadata')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('Date')),
                Tables\Columns\TextColumn::make('event_type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'payment_failed' => 'danger',
                        'subscription_created' => 'success',
                        'downgrade_scheduled' => 'warning',
                        'project_suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('gateway')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('billingProfile.name')
                    ->label(__('Billing Profile'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label(__('Filter by User'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('billing_profile_id')
                    ->relationship('billingProfile', 'name')
                    ->label(__('Filter by Billing Profile'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label(__('Filter by Project'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'payment_failed' => 'Payment Failed',
                        'subscription_created' => 'Subscription Created',
                        'downgrade_scheduled' => 'Downgrade Scheduled',
                        'project_suspended' => 'Project Suspended',
                        'webhook_received' => 'Webhook Received',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for logs
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListBillingLogs::route('/'),
            'view' => Pages\ViewBillingLog::route('/{record}'),
        ];
    }
}
