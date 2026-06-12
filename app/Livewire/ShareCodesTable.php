<?php

namespace App\Livewire;

use App\Models\OneTimeShareToken;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Str;
use Livewire\Component;

class ShareCodesTable extends Component implements HasTable
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $project = Filament::getTenant();

        return $table
            ->query(OneTimeShareToken::query()->where('project_id', $project->id))
            ->heading('Share Codes')
            ->description('Use these codes so that any user can join this project from the registration form. Each code can only be used once.')
            ->columns([
                TextColumn::make('token')
                    ->label('Code')
                    ->copyable()
                    ->copyMessage('Code copied!')
                    ->copyMessageDuration(1500)
                    ->extraAttributes(['class' => 'font-mono']),
                TextColumn::make('email')
                    ->label('Target Email')
                    ->default('Anyone'),
                TextColumn::make('used_at')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (OneTimeShareToken $record) => $record->used_at ? 'Used' : ($record->expires_at?->isPast() ? 'Expired' : 'Available'))
                    ->color(fn (OneTimeShareToken $record) => $record->used_at ? 'gray' : ($record->expires_at?->isPast() ? 'danger' : 'success')),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->default('Never'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Generate Share Code')
                    ->icon('heroicon-o-link')
                    ->action(function () use ($project) {
                        $code = 'APISHUB-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

                        $token = OneTimeShareToken::create([
                            'project_id' => $project->id,
                            'token' => $code,
                            'created_by' => auth()->id(),
                            'expires_at' => now()->addDays(30),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Share Code Generated')
                            ->body("Code: {$token->token}")
                            ->persistent()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.share-codes-table');
    }
}
