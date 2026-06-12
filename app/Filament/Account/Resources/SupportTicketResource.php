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

    protected static ?string $navigationLabel = 'Support Tickets';

    protected static ?string $navigationGroup = 'Account';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['project', 'user']);
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
                Forms\Components\Select::make('project_id')
                    ->label('Project (optional)')
                    ->relationship('project', 'name', fn (Builder $query) => $query->whereIn('id', auth()->user()->projects()->pluck('project_id')))
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('description')
                    ->label('Describe your request')
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
                    ->label('#'),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('—'),
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
                    ->placeholder('Open')
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
