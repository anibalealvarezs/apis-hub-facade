<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\Project;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UserProjectsTable extends BaseWidget
{
    public ?User $record = null;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->where('user_id', $this->record->id)
                    ->orWhereHas('users', function (Builder $query): void {
                        $query->where('users.id', $this->record->id);
                    })
            )
            ->heading(__('Projects'))
            ->description(__('Projects this user owns or is a member of.'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Project'))
                    ->sortable()
                    ->url(fn (Project $record): string => \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record->id])),

                Tables\Columns\TextColumn::make('subdomain')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('relationship')
                    ->label(__('Relationship'))
                    ->getStateUsing(fn (Project $record): string => $record->user_id === $this->record->id ? __('Owner') : __('Member'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        __('Owner'), 'Owner' => 'success',
                        __('Member'), 'Member' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('health_status')
                    ->label(__('Health'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'online' => 'success',
                        'error' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('billing_status')
                    ->label(__('Billing'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'past_due' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}