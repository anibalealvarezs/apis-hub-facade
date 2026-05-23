<?php

namespace App\Filament\Account\Resources\BillingProfileResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\BillingInvitation;
use App\Mail\BillingInvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

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
                Tables\Actions\Action::make('invite')
                    ->label('Invite User')
                    ->icon('heroicon-o-envelope')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->label('User Email'),
                        Forms\Components\Select::make('role')
                            ->options([
                                'member' => 'Member (Can use to pay)',
                            ])
                            ->default('member')
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $profile = $livewire->ownerRecord;
                        
                        // Check if already shared
                        $alreadyShared = $profile->sharedWithUsers()->where('users.email', $data['email'])->exists();
                        if ($alreadyShared) {
                            Notification::make()
                                ->title('User is already sharing this profile.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Check if already invited
                        $alreadyInvited = BillingInvitation::where('billing_profile_id', $profile->id)
                            ->where('email', $data['email'])
                            ->where('status', 'pending')
                            ->exists();
                            
                        if ($alreadyInvited) {
                            Notification::make()
                                ->title('User already has a pending invitation.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $invitation = BillingInvitation::create([
                            'billing_profile_id' => $profile->id,
                            'email' => $data['email'],
                            'role' => $data['role'],
                            'token' => Str::random(32),
                            'expires_at' => now()->addDays(7),
                        ]);

                        Mail::to($data['email'])->send(new BillingInvitationMail($invitation));

                        Notification::make()
                            ->title('Invitation Sent')
                            ->success()
                            ->send();
                    }),
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
