<?php

namespace App\Filament\App\Resources\DashboardResource\RelationManagers;

use App\Models\AssetGroup;
use App\Models\DashboardPublicView;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicViewsRelationManager extends RelationManager
{
    protected static string $relationship = 'publicViews';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) $ownerRecord->is_public;
    }

    public function form(Form $form): Form
    {
        $dashboard = $this->getOwnerRecord();
        $projectId = $dashboard->project_id;

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('View Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('asset_group_ids')
                    ->label(__('Locked Asset Groups'))
                    ->options(function () use ($dashboard, $projectId) {
                        $query = AssetGroup::where('project_id', $projectId);
                        $dashAllowed = (array) ($dashboard->controls['asset_group'] ?? []);
                        $dashAllowed = array_values(array_filter(array_map('strval', $dashAllowed)));
                        if (!empty($dashAllowed)) {
                            $query->whereIn('id', $dashAllowed);
                        }
                        return $query->pluck('name', 'id');
                    })
                    ->multiple()
                    ->searchable()
                    ->helperText(__('Select one or more asset groups to restrict the public view data. Leave empty to allow all asset groups permitted by the dashboard.')),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                        Forms\Components\Toggle::make('allow_pdf_export')
                            ->label(__('Allow PDF Export'))
                            ->helperText(__('Enable printing / PDF export in this public view'))
                            ->default(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([\Illuminate\Database\Eloquent\SoftDeletingScope::class]))
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('asset_group_ids')
                    ->label(__('Asset Groups'))
                    ->formatStateUsing(function ($state) {
                        $ids = is_array($state) ? $state : [];
                        if (empty($ids)) return null;
                        return AssetGroup::whereIn('id', $ids)->pluck('name')->join(', ');
                    })
                    ->placeholder(__('All Assets')),
                Tables\Columns\TextColumn::make('token')
                    ->label(__('Token'))
                    ->formatStateUsing(fn ($state) => substr($state, 0, 12) . '...')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyableState(fn (DashboardPublicView $record) => $record->getPublicUrl())
                    ->copyMessage(__('Public URL copied to clipboard')),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('allow_pdf_export')
                    ->label(__('PDF Export'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_link')
                    ->label(__('Copy Link'))
                    ->icon('heroicon-o-clipboard-document')
                    ->color('info')
                    ->action(function (DashboardPublicView $record) {
                        Notification::make()
                            ->title(__('Public URL: ') . $record->getPublicUrl())
                            ->success()
                            ->send();
                    })
                    ->alpineClickHandler(fn (DashboardPublicView $record): string => 'window.navigator.clipboard.writeText(' . \Illuminate\Support\Js::from($record->getPublicUrl()) . ')')
                    ->livewireClickHandlerEnabled(),
                Tables\Actions\Action::make('copy_embed')
                    ->label(__('Embed Code'))
                    ->icon('heroicon-o-code-bracket')
                    ->color('success')
                    ->modalHeading(__('Embed Public View'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->modalContent(function (DashboardPublicView $record) {
                        return view('filament.modals.embed-code', [
                            'pv' => $record,
                            'embedUrl' => $record->getEmbedUrl(),
                            'publicUrl' => $record->getPublicUrl(),
                        ]);
                    }),
                Tables\Actions\Action::make('regenerate_token')
                    ->label(__('Regenerate Token'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('Regenerate Public View Token?'))
                    ->modalDescription(__('This will invalidate all existing links and embed scripts for this public view immediately.'))
                    ->action(function (DashboardPublicView $record) {
                        $record->regenerateToken();
                        Notification::make()
                            ->title(__('Token regenerated successfully'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->can('edit_preferences')),
                Tables\Actions\EditAction::make()
                    ->visible(fn (DashboardPublicView $record) => !$record->trashed() && auth()->user()->can('edit_preferences')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (DashboardPublicView $record) => !$record->trashed() && auth()->user()->can('edit_preferences')),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn (DashboardPublicView $record) => $record->trashed() && auth()->user()->can('edit_preferences')),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn (DashboardPublicView $record) => $record->trashed() && auth()->user()->can('edit_preferences')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('edit_preferences')),
                ]),
            ]);
    }
}
