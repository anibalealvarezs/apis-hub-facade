<?php

namespace App\Filament\Account\Resources;

use App\Filament\Account\Resources\BillingProfileResource\Pages;
use App\Filament\Account\Resources\BillingProfileResource\RelationManagers;
use App\Models\BillingProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillingProfileResource extends Resource
{
    protected static ?string $model = BillingProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Billing & Payments';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        // Check if the user already owns a free tier billing profile
        $hasFreeProfile = BillingProfile::where('user_id', auth()->id())
            ->where('tier', 'free')
            ->exists();
            
        return !$hasFreeProfile;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Identity')->schema([
                    Forms\Components\Hidden::make('user_id')
                        ->default(auth()->id()),
                    Forms\Components\Toggle::make('is_default')
                        ->label('Make this my default billing profile')
                        ->default(false),
                    Forms\Components\Select::make('type')
                        ->options([
                            'individual' => 'Individual (Personal)',
                            'company' => 'Company (Business)',
                        ])
                        ->required()
                        ->default('individual')
                        ->live(),
                    Forms\Components\TextInput::make('name')
                        ->label(fn (Forms\Get $get) => $get('type') === 'company' ? 'Company Name' : 'Full Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('reference_name')
                        ->label('Referential Name (e.g. Personal Profile, Marketing Team Billing)')
                        ->maxLength(255)
                        ->helperText('A descriptive label to identify this profile in lists and selectors across the application.'),
                    Forms\Components\TextInput::make('tax_id')
                        ->label(fn (Forms\Get $get) => $get('type') === 'company' ? 'Tax ID / VAT / EIN' : 'Personal ID / RUT')
                        ->maxLength(255),
                ])->columns(2),

                Forms\Components\Section::make('Billing Address')->schema([
                    Forms\Components\TextInput::make('address_line_1')->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('city')->maxLength(255),
                    Forms\Components\TextInput::make('state')->maxLength(255),
                    Forms\Components\TextInput::make('postal_code')->maxLength(255),
                    Forms\Components\TextInput::make('country_code')->maxLength(2)->label('Country Code (ISO 2)'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_name')
                    ->label('Referential Name')
                    ->searchable()
                    ->placeholder('N/A (Uses Legal Name)'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Legal Name/Company')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'company' => 'info',
                        'individual', 'personal' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
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
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete Billing Profile')
                    ->modalDescription(function (BillingProfile $record) {
                        $count = $record->projects()->count();
                        if ($count > 0) {
                            return "WARNING: This profile is actively paying for {$count} project(s). If you delete it now, all attached projects will be IMMEDIATELY SUSPENDED and their infrastructure will be stopped. We highly recommend assigning them a different billing profile first. Are you absolutely sure?";
                        }
                        return 'Are you sure you want to delete this billing profile? This action cannot be undone.';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Delete Selected Billing Profiles')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $totalProjects = 0;
                            foreach ($records as $record) {
                                $totalProjects += $record->projects()->count();
                            }
                            if ($totalProjects > 0) {
                                return "WARNING: The selected profiles are actively paying for {$totalProjects} project(s) in total. If you delete them, ALL attached projects will be IMMEDIATELY SUSPENDED. We highly recommend assigning them a different billing profile first. Are you absolutely sure?";
                            }
                            return 'Are you sure you want to delete these billing profiles?';
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SharedWithUsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillingProfiles::route('/'),
            'create' => Pages\CreateBillingProfile::route('/create'),
            'edit' => Pages\EditBillingProfile::route('/{record}/edit'),
        ];
    }
}
