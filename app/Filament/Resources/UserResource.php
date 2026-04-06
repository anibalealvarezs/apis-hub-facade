<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages as UserPages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'SaaS Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Identity')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn ($livewire) => $livewire instanceof UserPages\CreateUser)
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Access Control')
                    ->schema([
                        Forms\Components\Toggle::make('is_admin')
                            ->helperText('Grant full access to this admin panel.')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->helperText('Active users can log in to the portal.')
                            ->default(true)
                            ->required(),
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_admin'),
            ])
            ->actions([
                Tables\Actions\Action::make('logoutOtherDevices')
                    ->label('Force Logout')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        // Set invalidation timestamp
                        $record->update(['logout_at' => now()]);

                        // Clear native Laravel sessions (if in DB)
                        DB::table('sessions')
                            ->where('user_id', $record->getAuthIdentifier())
                            ->delete();

                        // Clear Filament Breezy sessions
                        DB::table('breezy_sessions')
                            ->where('authenticatable_id', $record->getAuthIdentifier())
                            ->where('authenticatable_type', User::class)
                            ->delete();

                        Notification::make()
                            ->title('User sessions invalidated and cleared across all platforms.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('impersonate')
                    ->label('Log in as')
                    ->icon('heroicon-o-finger-print')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        session()->put('impersonator_id', Auth::id());
                        Auth::login($record);
                        
                        return redirect('/app');
                    }),

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
            'index' => UserPages\ListUsers::route('/'),
            'create' => UserPages\CreateUser::route('/create'),
            'edit' => UserPages\EditUser::route('/{record}/edit'),
        ];
    }
}
