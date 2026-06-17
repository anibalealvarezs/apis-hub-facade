<?php

namespace App\Filament\Account\Resources;

use App\Filament\Account\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Payments');
    }


    

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('billingProfile', fn($q) => $q->where('user_id', auth()->id()));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only view
                Forms\Components\TextInput::make('invoice_number')
                    ->label('Invoice Number')
                    ->disabled()
                    ->formatStateUsing(fn ($state, $record) => $record->fiscal_status === 'reconciled' ? $state : 'Pending Reconciliation'),
                Forms\Components\TextInput::make('gateway')
                    ->disabled(),
                Forms\Components\TextInput::make('gateway_invoice_id')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->formatStateUsing(fn ($state, $record) => $record->fiscal_status === 'reconciled' ? $state : 'Pending')
                    ->badge()
                    ->color(fn ($record) => $record->fiscal_status === 'reconciled' ? 'success' : 'warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'stripe' => 'info',
                        'paypal' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Invoice $record) => route('invoices.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record) => $record->fiscal_status === 'reconciled'),
            ])
            ->bulkActions([
                // No bulk delete for invoices
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
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
