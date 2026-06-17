<?php

namespace App\Filament\Account\Resources;

use App\Filament\Account\Resources\SupportTicketResource\Pages;
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

    public static function getNavigationLabel(): string
    {
        return __('Support Tickets');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Account');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->accessibleBy(auth()->user())
            ->with(['project', 'billingProfile', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'data_deletion' => 'Data Deletion',
                        'data_download' => 'Data Download',
                        'general' => 'General Support',
                    ])
                    ->required(),
                Forms\Components\Select::make('association_type')
                    ->label(__('Associate with'))
                    ->options([
                        'none' => 'Nothing (Account-level request)',
                        'project' => 'Project',
                        'billing_profile' => 'Billing Profile',
                    ])
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('project_id', null);
                        $set('billing_profile_id', null);
                    }),
                Forms\Components\Select::make('project_id')
                    ->label(__('Project'))
                    ->relationship('project', 'name', function (Builder $query) {
                        $user = auth()->user();
                        $query->where('user_id', $user->id)
                            ->orWhereHas('users', fn (Builder $q) => $q->where('users.id', $user->id));
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get) => $get('association_type') === 'project'),
                Forms\Components\Select::make('billing_profile_id')
                    ->label(__('Billing Profile'))
                    ->relationship('billingProfile', 'name', function (Builder $query) {
                        $query->where('user_id', auth()->id());
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get) => $get('association_type') === 'billing_profile'),
                Forms\Components\Textarea::make('description')
                    ->label(__('Describe your request'))
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('#')),
                Tables\Columns\TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->placeholder(__('—')),
                Tables\Columns\TextColumn::make('billingProfile.name')
                    ->label(__('Billing Profile'))
                    ->placeholder(__('—')),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->dateTime()
                    ->placeholder(__('Open'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'create' => Pages\CreateSupportTicket::route('/create'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
        ];
    }
}
