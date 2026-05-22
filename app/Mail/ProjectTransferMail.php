<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\ProjectTransfer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectTransferMail extends Mailable
{
    use Queueable, SerializesModels;

    public $transfer;
    public $project;
    public $toUser;

    /**
     * Create a new message instance.
     */
    public function __construct(ProjectTransfer $transfer, Project $project, User $toUser)
    {
        $this->transfer = $transfer;
        $this->project = $project;
        $this->toUser = $toUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Transferencia de Propiedad: ' . $this->project->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.project_transfer',
            with: [
                'acceptUrl' => url('/app/transfers/' . $this->transfer->token . '/accept'),
                'fromUser' => $this->transfer->fromUser->name,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
