<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\TicketMessage;
use App\Notifications\TicketStatusChangedNotification;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static string $view = 'filament.pages.view-support-ticket';

    public function getTitle(): string
    {
        return "Ticket #{$this->record->id} — {$this->record->type}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changeStatus')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Forms\Components\Select::make('status')
                        ->options([
                            'started' => 'Started',
                            'in_progress' => 'In Progress',
                            'waiting_on_user' => 'Waiting on User',
                            'closed' => 'Closed',
                        ])
                        ->default($this->record->status)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $oldStatus = $this->record->status;

                    $this->record->update([
                        'status' => $data['status'],
                        'closed_at' => $data['status'] === 'closed' ? now() : null,
                    ]);

                    TicketMessage::create([
                        'support_ticket_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'message' => "Status changed to: {$data['status']}",
                    ]);

                    $this->refreshFormData(['status']);

                    if (in_array($data['status'], ['waiting_on_user', 'closed']) && $this->record->user) {
                        $this->record->user->notify(new TicketStatusChangedNotification($this->record, $oldStatus));
                    }

                    Notification::make()
                        ->title('Status Updated')
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }

    public function getMessages()
    {
        return $this->record->messages()->with('user')->orderBy('created_at')->get();
    }
}
