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

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Users');
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('SaaS Management');
    }

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
                            ->helperText(__('Grant full access to this admin panel.'))
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->helperText(__('Active users can log in to the portal.'))
                            ->default(true)
                            ->required(),

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
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label(__('Admin'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_online')
                    ->label(__('Online'))
                    ->boolean()
                    ->state(function (User $record) {
                        return DB::table('sessions')
                            ->where('user_id', $record->id)
                            ->where('last_activity', '>', time() - config('session.lifetime', 120) * 60)
                            ->exists();
                    })
                    ->sortable(false),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label(__('Verified'))
                    ->boolean()
                    ->state(fn (User $record) => $record->email_verified_at !== null)
                    ->sortable(),
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
                    ->label(__('Force Logout'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $now = now();

                        // Force direct database write
                        DB::table('users')
                            ->where('id', $record->getKey())
                            ->update(['logout_at' => $now]);

                        \Illuminate\Support\Facades\Log::info('FORCE LOGOUT TRIGGERED', [
                            'user' => $record->email,
                            'new_logout_at' => $now->toDateTimeString(),
                        ]);

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
                            ->title(__('User sessions invalidated and cleared across all platforms.'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('impersonate')
                    ->label(__('Log in as'))
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
