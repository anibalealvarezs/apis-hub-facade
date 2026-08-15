<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Blade;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Invoices');
    }

    public static function getModelLabel(): string
    {
        return __('Invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Invoices');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Financials');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->disabled()
                            ->label('Commercial Invoice Number'),
                        Forms\Components\TextInput::make('control_number')
                            ->required()
                            ->label('SENIAT Control Number')
                            ->disabled(fn (?Invoice $record) => $record?->fiscal_status === 'reconciled')
                            ->helperText('Assigning a permanent control number will lock this invoice.'),
                        Forms\Components\Select::make('fiscal_status')
                            ->options([
                                'pending' => 'Pending',
                                'reconciled' => 'Reconciled',
                                'annulled' => 'Annulled',
                            ])
                            ->disabled(fn (?Invoice $record) => $record?->fiscal_status === 'reconciled'),
                    ])->columns(2),
                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->disabled()
                            ->label('Total (USD)'),
                        Forms\Components\TextInput::make('local_total')
                            ->disabled()
                            ->label('Total (VES)'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('control_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fiscal_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reconciled' => 'success',
                        'annulled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fiscal_status')
                    ->options([
                        'pending' => 'Pending',
                        'reconciled' => 'Reconciled',
                        'annulled' => 'Annulled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview_pdf')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->action(function (Invoice $record) {
                        $pdf = Pdf::loadView('pdf.invoice', [
                            'invoice' => $record,
                            'profile' => $record->billingProfile,
                            'subscription' => $record->subscription,
                        ]);
                        return response()->streamDownload(fn () => print($pdf->output()), "preview-invoice-{$record->id}.pdf");
                    }),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
