<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Support';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'project', 'billingProfile', 'internalUsers', 'internalProjects', 'internalBillingProfiles']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable(['name', 'email'])
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'data_deletion' => 'Data Deletion',
                                'data_download' => 'Data Download',
                                'general' => 'General Support',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'started' => 'Started',
                                'in_progress' => 'In Progress',
                                'waiting_on_user' => 'Waiting on User',
                                'closed' => 'Closed',
                            ])
                            ->required(),
                        Forms\Components\Select::make('project_id')
                            ->label('Associated Project')
                            ->relationship('project', 'name')
                            ->searchable(['name', 'subdomain'])
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('billing_profile_id')
                            ->label('Associated Billing Profile')
                            ->relationship('billingProfile', 'name')
                            ->searchable(['name'])
                            ->preload()
                            ->nullable(),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('external_ref')
                            ->label('External Reference')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Internal Associations')
                    ->description('These are visible only to admins. Use to tag related records for filtering and context.')
                    ->schema([
                        Forms\Components\Select::make('internalUsers')
                            ->label('Related Users')
                            ->multiple()
                            ->relationship('internalUsers', 'name')
                            ->searchable(['name', 'email'])
                            ->preload(),
                        Forms\Components\Select::make('internalProjects')
                            ->label('Related Projects')
                            ->multiple()
                            ->options(fn () => \App\Models\Project::select('id', 'name')->withTrashed()->pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\Select::make('internalBillingProfiles')
                            ->label('Related Billing Profiles')
                            ->multiple()
                            ->relationship('internalBillingProfiles', 'name')
                            ->searchable(['name'])
                            ->preload(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('billingProfile.name')
                    ->label('Billing Profile')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'data_deletion' => 'Data Deletion',
                        'data_download' => 'Data Download',
                        'general' => 'General',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'data_deletion' => 'danger',
                        'data_download' => 'warning',
                        'general' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'started' => 'gray',
                        'in_progress' => 'warning',
                        'waiting_on_user' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('internalUsers.name')
                    ->label('Internal Users')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('internalProjects.name')
                    ->label('Internal Projects')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'started' => 'Started',
                        'in_progress' => 'In Progress',
                        'waiting_on_user' => 'Waiting on User',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'data_deletion' => 'Data Deletion',
                        'data_download' => 'Data Download',
                        'general' => 'General',
                    ]),
                Tables\Filters\SelectFilter::make('internalUsers')
                    ->label('Internal User')
                    ->options(fn () => \App\Models\User::select('id', 'name')
                        ->whereHas('supportTicketInternalAssociations', fn (Builder $q) => $q->whereNotNull('ticket_internal_users.user_id'))
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'] ?? null, fn (Builder $q, $val) => $q->whereHas('internalUsers', fn (Builder $sub) => $sub->where('users.id', $val)))),
                Tables\Filters\SelectFilter::make('internalProjects')
                    ->label('Internal Project')
                    ->options(fn () => \App\Models\Project::select('id', 'name')
                        ->whereHas('supportTicketInternalAssociations', fn (Builder $q) => $q->whereNotNull('ticket_internal_projects.project_id'))
                        ->withTrashed()
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'] ?? null, fn (Builder $q, $val) => $q->whereHas('internalProjects', fn (Builder $sub) => $sub->where('projects.id', $val)))),
                Tables\Filters\SelectFilter::make('internalBillingProfiles')
                    ->label('Internal Billing Profile')
                    ->options(fn () => \App\Models\BillingProfile::select('id', 'name')
                        ->whereHas('supportTicketInternalAssociations', fn (Builder $q) => $q->whereNotNull('ticket_internal_billing_profiles.billing_profile_id'))
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'] ?? null, fn (Builder $q, $val) => $q->whereHas('internalBillingProfiles', fn (Builder $sub) => $sub->where('billing_profiles.id', $val)))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
