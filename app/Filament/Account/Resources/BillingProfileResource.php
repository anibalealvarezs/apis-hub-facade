<?php

namespace App\Filament\Account\Resources;

use App\Filament\Account\Resources\BillingProfileResource\Pages;
use App\Filament\Account\Resources\BillingProfileResource\RelationManagers;
use App\Models\BillingProfile;
use App\Services\BillingLifecycleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BillingProfileResource extends Resource
{
    protected static ?string $model = BillingProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Payments');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\Project::whereHas('billingProfile', function ($q) {
            $q->where('user_id', auth()->id());
        })
            ->where('billing_status', 'pending_approval')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $userId = auth()->id();

        return parent::getEloquentQuery()
            ->where(function (Builder $query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sharedWithUsers', fn ($q) => $q->where('user_id', $userId));
            });
    }

    public static function canCreate(): bool
    {
        // Check if the user already owns a free tier billing profile
        $hasFreeProfile = BillingProfile::where('user_id', auth()->id())
            ->where('tier', 'free')
            ->exists();

        return ! $hasFreeProfile;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->user_id === auth()->id();
    }

    public static function canDelete(Model $record): bool
    {
        return $record->user_id === auth()->id();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Identity')->schema([
                    Forms\Components\Hidden::make('user_id')
                        ->default(auth()->id()),
                    Forms\Components\Toggle::make('is_default')
                        ->label(__('Make this my default billing profile'))
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
                        ->label(__('Referential Name (e.g. Personal Profile, Marketing Team Billing)'))
                        ->required()
                        ->maxLength(255)
                        ->helperText(__('A descriptive label to identify this profile in lists and selectors across the application.')),
                    Forms\Components\TextInput::make('tax_id')
                        ->label(fn (Forms\Get $get) => $get('type') === 'company' ? 'Tax ID / VAT / EIN' : 'Personal ID / RUT')
                        ->maxLength(255),
                ])->columns(2),

                Forms\Components\Section::make('Billing Address')->schema([
                    Forms\Components\TextInput::make('address_line_1')->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('city')->maxLength(255),
                    Forms\Components\TextInput::make('state')->maxLength(255),
                    Forms\Components\TextInput::make('postal_code')->maxLength(255),
                    Forms\Components\TextInput::make('country_code')->maxLength(2)->label(__('Country Code (ISO 2)')),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner')
                    ->label(__('Owner'))
                    ->getStateUsing(function (BillingProfile $record) {
                        return $record->user_id === auth()->id() ? 'You' : ($record->user?->name ?? 'Unknown');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->extraAttributes(fn (BillingProfile $record) => [
                        'class' => $record->user_id !== auth()->id() ? 'italic text-gray-500 dark:text-gray-400' : 'font-medium',
                    ]),
                Tables\Columns\TextColumn::make('reference_name')
                    ->label(__('Referential Name'))
                    ->searchable()
                    ->placeholder(__('N/A (Uses Legal Name)')),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Legal Name/Company'))
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
                Tables\Columns\TextColumn::make('tier')
                    ->badge()
                    ->color(fn (\App\Enums\UserTier $state): string => match ($state->value) {
                        'free' => 'gray',
                        'starter' => 'success',
                        'growth' => 'warning',
                        'premium' => 'danger',
                        'enterprise' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (\App\Enums\UserTier $state): string => ucfirst($state->value)),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label(__('Projects'))
                    ->counts('projects')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shared_users_count')
                    ->label(__('Shared with'))
                    ->counts('sharedWithUsers'),
                Tables\Columns\TextColumn::make('quota')
                    ->label(__('Usage'))
                    ->html()
                    ->state(function (BillingProfile $record): string {
                        $service = app(BillingLifecycleService::class);
                        $userId = auth()->id();
                        $isOwner = $record->user_id === $userId;

                        $maxProjects = $service->getMaxProjectsForTier($record->tier);
                        $maxAccounts = $service->getMaxAccountsForTier($record->tier);

                        if ($isOwner) {
                            $projectCount = $record->projects()->count();
                            $projectsToScan = $record->projects;
                        } else {
                            $userProjectIds = auth()->user()->projects()
                                ->where('billing_profile_id', $record->id)
                                ->pluck('projects.id');
                            $projectCount = $userProjectIds->count();
                            $projectsToScan = $record->projects()->whereIn('id', $userProjectIds)->get();

                            $totalProjectCount = $record->projects()->count();
                            $maxProjects = $projectCount + max(0, $maxProjects - $totalProjectCount);
                        }

                        $assetCount = 0;
                        $assetKeys = ['sites', 'ad_accounts', 'pages', 'locations', 'profiles', 'accounts', 'shops', 'properties'];
                        foreach ($projectsToScan as $project) {
                            $syncConfig = $project->sync_config ?? [];
                            if (! is_array($syncConfig)) {
                                continue;
                            }

                            foreach ($syncConfig as $channelKey => $channelConfig) {
                                if (! is_array($channelConfig) || empty($channelConfig['enabled'])) {
                                    continue;
                                }

                                foreach ($assetKeys as $assetKey) {
                                    $assets = $channelConfig[$assetKey] ?? ($channelConfig['assets'][$assetKey] ?? null);
                                    if (! is_array($assets)) {
                                        continue;
                                    }

                                    foreach ($assets as $asset) {
                                        if (is_array($asset) && ! empty($asset['enabled']) && empty($asset['lost_access'])) {
                                            $assetCount++;
                                        }
                                    }
                                }
                            }
                        }

                        if (! $isOwner) {
                            $totalAssetCount = 0;
                            foreach ($record->projects as $project) {
                                $syncConfig = $project->sync_config ?? [];
                                if (! is_array($syncConfig)) {
                                    continue;
                                }
                                foreach ($syncConfig as $channelKey => $channelConfig) {
                                    if (! is_array($channelConfig) || empty($channelConfig['enabled'])) {
                                        continue;
                                    }
                                    foreach ($assetKeys as $assetKey) {
                                        $assets = $channelConfig[$assetKey] ?? ($channelConfig['assets'][$assetKey] ?? null);
                                        if (! is_array($assets)) {
                                            continue;
                                        }
                                        foreach ($assets as $asset) {
                                            if (is_array($asset) && ! empty($asset['enabled']) && empty($asset['lost_access'])) {
                                                $totalAssetCount++;
                                            }
                                        }
                                    }
                                }
                            }
                            $maxAccounts = $assetCount + max(0, $maxAccounts - $totalAssetCount);
                        }

                        $pct = fn ($used, $max) => $max > 0 ? round(($used / $max) * 100) : 0;
                        $color = fn ($p) => $p >= 90 ? '#ef4444' : ($p >= 70 ? '#f59e0b' : '#22c55e');

                        $pPct = $pct($projectCount, $maxProjects);
                        $aPct = $pct($assetCount, $maxAccounts);

                        return '
                            <div class="text-xs leading-relaxed whitespace-nowrap">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span class="font-semibold w-16">Projects</span>
                                    <span class="text-gray-500 dark:text-gray-400">' . $projectCount . '/' . $maxProjects . '</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div style="width:' . $pPct . '%;height:100%;background:' . $color($pPct) . ';border-radius:9999px;transition:width .3s"></div>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1.5 mb-0.5">
                                    <span class="font-semibold w-16">Accounts</span>
                                    <span class="text-gray-500 dark:text-gray-400">' . $assetCount . '/' . $maxAccounts . '</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div style="width:' . $aPct . '%;height:100%;background:' . $color($aPct) . ';border-radius:9999px;transition:width .3s"></div>
                                </div>
                            </div>';
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (BillingProfile $record) => $record->user_id === auth()->id()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (BillingProfile $record) => $record->user_id === auth()->id())
                    ->modalHeading(__('Delete Billing Profile'))
                    ->modalDescription(function (BillingProfile $record) {
                        $count = $record->projects()->count();
                        if ($count > 0) {
                            return __('WARNING: This profile is actively paying for :count project(s). If you delete it now, all attached projects will be IMMEDIATELY SUSPENDED and their infrastructure will be stopped. We highly recommend assigning them a different billing profile first. Are you absolutely sure?', ['count' => $count]);
                        }

                        return __('Are you sure you want to delete this billing profile? This action cannot be undone.');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->billingProfiles()->exists())
                        ->modalHeading(__('Delete Selected Billing Profiles'))
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $totalProjects = 0;
                            foreach ($records as $record) {
                                $totalProjects += $record->projects()->count();
                            }
                            if ($totalProjects > 0) {
                                return __('WARNING: The selected profiles are actively paying for :count project(s) in total. If you delete them, ALL attached projects will be IMMEDIATELY SUSPENDED. We highly recommend assigning them a different billing profile first. Are you absolutely sure?', ['count' => $totalProjects]);
                            }

                            return __('Are you sure you want to delete the selected billing profiles? This action cannot be undone.');
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SharedWithUsersRelationManager::class,
            RelationManagers\ProjectsRelationManager::class,
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
