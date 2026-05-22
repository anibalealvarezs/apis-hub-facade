<?php

namespace App\Livewire;

use App\Models\Project;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class ArchivedProjectsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public static function canView(): bool
    {
        return true;
    }

    public static $sort = 10;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Solo proyectos que el usuario posea (user_id) y que estén eliminados (trashed)
                Project::onlyTrashed()->where('user_id', auth()->id())
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre del Proyecto'),
                TextColumn::make('subdomain')
                    ->label('Subdominio'),
                TextColumn::make('deleted_at')
                    ->label('Eliminado El')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('restore')
                    ->label('Restaurar')
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading('Restaurar Proyecto')
                    ->modalDescription('¿Estás seguro de que quieres restaurar este proyecto? Esto rehabilitará el tráfico al dominio y restaurará el acceso.')
                    ->action(function (Project $record) {
                        $record->restore();
                        // Despachar Job para rehabilitar el Caddy config
                        \App\Jobs\RestoreProjectDomainJob::dispatch($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Proyecto Restaurado')
                            ->body('El proyecto ha sido restaurado exitosamente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No hay proyectos archivados')
            ->emptyStateDescription('Los proyectos eliminados aparecerán aquí durante 30 días antes de su destrucción final.');
    }

    public function render()
    {
        return view('livewire.archived-projects-table');
    }
}
