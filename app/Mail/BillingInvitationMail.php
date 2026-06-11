<?php

namespace App\Mail;

use App\Models\BillingInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BillingInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public BillingInvitation $invitation;

    public function __construct(BillingInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation to join Billing Profile: ' . $this->invitation->billingProfile->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.billing-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
