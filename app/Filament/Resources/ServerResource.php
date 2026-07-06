<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages\CreateServer as CreateServerPage;
use App\Filament\Resources\ServerResource\Pages\EditServer as EditServerPage;
use App\Filament\Resources\ServerResource\Pages\ListServers as ListServersPage;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    public static function getNavigationGroup(): ?string
    {
        return __('Infrastructure');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->description(__('Identify the server and its readiness status.'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_ready')
                            ->required()
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('SSH Configuration')
                    ->description(__('These credentials are used by the orchestrator (DeployerService) to connect.'))
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->required()
                            ->maxLength(255)
                            ->label(__('IP Address / Hostname')),
                        Forms\Components\TextInput::make('ssh_port')
                            ->required()
                            ->numeric()
                            ->default(22),
                        Forms\Components\TextInput::make('ssh_user')
                            ->required()
                            ->maxLength(255)
                            ->default('root'),
                        Forms\Components\Textarea::make('ssh_private_key')
                            ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                            ->dehydrated(fn ($state) => filled($state))
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('IP'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('ssh_user')
                    ->label(__('User')),
                Tables\Columns\IconColumn::make('is_ready')
                    ->boolean()
                    ->label(__('Ready'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => ListServersPage::route('/'),
            'create' => CreateServerPage::route('/create'),
            'edit' => EditServerPage::route('/{record}/edit'),
        ];
    }
}
