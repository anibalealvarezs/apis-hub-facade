<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\BillingProfile;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UserBillingProfilesTable extends BaseWidget
{
    public ?User $record = null;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BillingProfile::query()
                    ->where('user_id', $this->record->id)
                    ->orWhereHas('sharedWithUsers', function (Builder $query) {
                        $query->where('users.id', $this->record->id);
                    })
            )
            ->heading('Accessed Billing Profiles')
            ->description('Billing profiles this user owns or has been invited to collaborate on.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Profile Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Relationship')
                    ->getStateUsing(function (BillingProfile $record): string {
                        return $record->user_id === $this->record->id ? 'Owner' : 'Shared';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Owner' => 'success',
                        'Shared' => 'info',
                    }),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->url(fn (BillingProfile $record) => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $record->user_id]))
                    ->searchable(),

                Tables\Columns\TextColumn::make('tier')
                    ->badge()
                    ->color(fn (\App\Enums\UserTier $state): string => match ($state) {
                        \App\Enums\UserTier::FREE => 'gray',
                        \App\Enums\UserTier::PRO => 'info',
                        \App\Enums\UserTier::ULTRA => 'success',
                        \App\Enums\UserTier::FOUNDER => 'warning',
                        \App\Enums\UserTier::ENTERPRISE => 'success',
                        \App\Enums\UserTier::SUSPENDED => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_tier')
                    ->label('Change Tier')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalHeading('Manually Change Billing Tier')
                    ->modalDescription('WARNING: Manually changing a tier bypasses Stripe/PayPal synchronization. Do this only for special cases.')
                    ->form([
                        Select::make('tier')
                            ->options(\App\Enums\UserTier::class)
                            ->required()
                            ->default(fn (BillingProfile $record) => $record->tier),
                        TextInput::make('confirmation')
                            ->label('Type "CONFIRM" to proceed')
                            ->required()
                            ->rule('in:CONFIRM,confirm,Confirm')
                            ->helperText('You must explicitly type confirm to apply this change.'),
                    ])
                    ->action(function (BillingProfile $record, array $data) {
                        $record->tier = $data['tier'];
                        $record->save();
                        Notification::make()
                            ->success()
                            ->title('Tier updated successfully')
                            ->send();
                    }),
            ]);
    }
}
