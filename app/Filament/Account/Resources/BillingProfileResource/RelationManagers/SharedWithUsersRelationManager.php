<?php

namespace App\Filament\Account\Resources\BillingProfileResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SharedWithUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'sharedWithUsers';

    protected static ?string $title = 'Shared Users';
    
    protected static ?string $recordTitleAttribute = 'email';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // We use AttachAction for assigning existing users, so this form might not strictly be used for creating users.
                // But if they edit the pivot (role), we need this:
                Forms\Components\Select::make('role')
                    ->options([
                        'member' => 'Member (Can use to pay)',
                    ])
                    ->default('member')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Share with User')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Select User')
                            ->searchable(),
                        Forms\Components\Hidden::make('role')->default('member'),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Revoke Access'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
