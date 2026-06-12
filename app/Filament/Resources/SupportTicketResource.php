<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
                            ->searchable()
                            ->allowHtml()
                            ->required()
                            ->options(fn (Get $get) => static::getUserOptionsByIds($get('user_id') ? [$get('user_id')] : []))
                            ->getSearchResultsUsing(fn (string $search) => static::getUserSearchResults($search))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getUserOptionLabel($value))
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('project_id', null) && $set('billing_profile_id', null)),
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
                            ->searchable()
                            ->allowHtml()
                            ->options(fn (Get $get) => static::getProjectOptionsForUser($get('user_id')))
                            ->disabled(fn (Get $get) => blank($get('user_id')))
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('billing_profile_id', null))
                            ->live()
                            ->nullable(),
                        Forms\Components\Select::make('billing_profile_id')
                            ->label('Associated Billing Profile')
                            ->searchable()
                            ->allowHtml()
                            ->options(fn (Get $get) => static::getBillingProfileOptionsForUser($get('user_id')))
                            ->disabled(fn (Get $get) => blank($get('user_id')))
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('project_id', null))
                            ->live()
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
                            ->searchable()
                            ->allowHtml()
                            ->options(fn (Get $get) => static::getUserOptionsByIds($get('internalUsers') ?? []))
                            ->getSearchResultsUsing(fn (string $search) => static::getUserSearchResults($search))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getUserOptionLabel($value))
                            ->live(),
                        Forms\Components\Select::make('internalProjects')
                            ->label('Related Projects')
                            ->multiple()
                            ->searchable()
                            ->allowHtml()
                            ->options(fn (Get $get) => static::getInternalProjectOptions(
                                $get('internalUsers') ?? [],
                                $get('internalProjects') ?? []
                            ))
                            ->disabled(fn (Get $get) => blank($get('internalUsers'))),
                        Forms\Components\Select::make('internalBillingProfiles')
                            ->label('Related Billing Profiles')
                            ->multiple()
                            ->searchable()
                            ->allowHtml()
                            ->options(fn (Get $get) => static::getInternalBillingProfileOptions(
                                $get('internalUsers') ?? [],
                                $get('internalBillingProfiles') ?? []
                            ))
                            ->disabled(fn (Get $get) => blank($get('internalUsers'))),
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
                    ->options(fn () => User::select('id', 'name')
                        ->whereHas('supportTicketInternalAssociations', fn (Builder $q) => $q->whereNotNull('ticket_internal_users.user_id'))
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'] ?? null, fn (Builder $q, $val) => $q->whereHas('internalUsers', fn (Builder $sub) => $sub->where('users.id', $val)))),
                Tables\Filters\SelectFilter::make('internalProjects')
                    ->label('Internal Project')
                    ->options(fn () => Project::select('id', 'name')
                        ->whereHas('supportTicketInternalAssociations', fn (Builder $q) => $q->whereNotNull('ticket_internal_projects.project_id'))
                        ->withTrashed()
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'] ?? null, fn (Builder $q, $val) => $q->whereHas('internalProjects', fn (Builder $sub) => $sub->where('projects.id', $val)))),
                Tables\Filters\SelectFilter::make('internalBillingProfiles')
                    ->label('Internal Billing Profile')
                    ->options(fn () => BillingProfile::select('id', 'name')
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
            'create' => Pages\CreateSupportTicket::route('/create'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }

    private static function getUserSearchResults(string $search): array
    {
        if (strlen($search) < 3) {
            return [];
        }

        return User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user) => [
                $user->id => static::formatUserOptionHtml($user),
            ])
            ->toArray();
    }

    private static function getUserOptionLabel($value): ?string
    {
        $user = User::find($value);
        if (!$user) {
            return null;
        }
        return "{$user->name} ({$user->email})";
    }

    private static function getUserOptionsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return User::whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (User $user) => [
                $user->id => static::formatUserOptionHtml($user),
            ])
            ->toArray();
    }

    private static function formatUserOptionHtml(User $user): string
    {
        $name = e($user->name);
        $email = e($user->email);
        return "<span class=\"font-semibold\">{$name}</span> <span class=\"text-gray-400\">— {$email}</span>";
    }

    private static function getProjectOptionsForUser($userId): array
    {
        if (blank($userId)) {
            return [];
        }

        $projects = Project::with('user:id,name')
            ->where(function (Builder $q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('users', fn (Builder $sub) => $sub->where('users.id', $userId));
            })
            ->get();

        return static::formatOptionsWithOwner($projects);
    }

    private static function getBillingProfileOptionsForUser($userId): array
    {
        if (blank($userId)) {
            return [];
        }

        $profiles = BillingProfile::with('user:id,name')
            ->where(function (Builder $q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('sharedWithUsers', fn (Builder $sub) => $sub->where('users.id', $userId));
            })
            ->get();

        return static::formatOptionsWithOwner($profiles);
    }

    private static function getInternalProjectOptions(array $userIds, array $selectedIds): array
    {
        if (empty($userIds) && empty($selectedIds)) {
            return [];
        }

        $projects = Project::with('user:id,name')
            ->where(function (Builder $q) use ($userIds) {
                if (!empty($userIds)) {
                    $q->whereIn('user_id', $userIds)
                      ->orWhereHas('users', fn (Builder $sub) => $sub->whereIn('users.id', $userIds));
                }
            })
            ->when($selectedIds, fn (Builder $q) => $q->orWhereIn('id', $selectedIds))
            ->get();

        return static::formatOptionsWithOwner($projects);
    }

    private static function getInternalBillingProfileOptions(array $userIds, array $selectedIds): array
    {
        if (empty($userIds) && empty($selectedIds)) {
            return [];
        }

        $profiles = BillingProfile::with('user:id,name')
            ->where(function (Builder $q) use ($userIds) {
                if (!empty($userIds)) {
                    $q->whereIn('user_id', $userIds)
                      ->orWhereHas('sharedWithUsers', fn (Builder $sub) => $sub->whereIn('users.id', $userIds));
                }
            })
            ->when($selectedIds, fn (Builder $q) => $q->orWhereIn('id', $selectedIds))
            ->get();

        return static::formatOptionsWithOwner($profiles);
    }

    private static function formatOptionsWithOwner($models): array
    {
        $options = [];
        foreach ($models as $model) {
            $label = $model instanceof BillingProfile
                ? e($model->display_name)
                : e($model->name);
            $owner = e($model->user?->name ?? 'No owner');
            $options[$model->id] = "<span class=\"font-semibold\">{$label}</span> <span class=\"text-gray-400\">— {$owner}</span>";
        }
        return $options;
    }
}
