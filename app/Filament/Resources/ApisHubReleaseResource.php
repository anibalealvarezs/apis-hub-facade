<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApisHubReleaseResource\Pages\CreateApisHubRelease as CreatePage;
use App\Filament\Resources\ApisHubReleaseResource\Pages\EditApisHubRelease as EditPage;
use App\Filament\Resources\ApisHubReleaseResource\Pages\ListApisHubReleases as ListPage;
use App\Models\ApisHubRelease;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApisHubReleaseResource extends Resource
{
    protected static ?string $model = ApisHubRelease::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    public static function getNavigationLabel(): string
    {
        return __('APIs Hub Releases');
    }


    

    
    public static function getNavigationGroup(): ?string
    {
        return __('Infrastructure');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Release Info')
                    ->description(__('Version tag and activation status.'))
                    ->schema([
                        Forms\Components\TextInput::make('version_tag')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder(__('e.g. v1.14.0'))
                            ->helperText(__('Must match an existing Git tag on the remote repository.')),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active (default fallback)'))
                            ->default(false)
                            ->helperText(__('Projects without a pinned release will use the active release.')),
                    ])->columns(2),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder(__('Brief summary of this release.')),
                        Forms\Components\Textarea::make('changelog')
                            ->rows(6)
                            ->placeholder(__('Markdown-formatted changelog notes.')),
                    ]),

                Forms\Components\Section::make('Channel Schemas')
                    ->description(__('Auto-generated from ChannelProfileRegistry. Read-only in this form.'))
                    ->schema([
                        Forms\Components\KeyValue::make('supported_channels')
                            ->label(__('Supported Channels')),
                        Forms\Components\Textarea::make('config_schemas')
                            ->label(__('Config Schemas (JSON)'))
                            ->rows(4)
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state),
                    ]),

                Forms\Components\Section::make('Upgrade Commands')
                    ->description(__('Bash commands to run on the remote instance after git checkout during upgrade.'))
                    ->schema([
                        Forms\Components\Repeater::make('upgrade_commands')
                            ->label(__('Commands'))
                            ->addActionLabel('Add command')
                            ->schema([
                                Forms\Components\Textarea::make('command')
                                    ->label(__('Command'))
                                    ->rows(2)
                                    ->placeholder(__('e.g. docker compose exec -T master php bin/cli.php orm:schema-tool:update --force'))
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->grid(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version_tag')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('Active'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('supported_channels')
                    ->label(__('Channels'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label(__('Projects'))
                    ->counts('projects')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => ListPage::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
