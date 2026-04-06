<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Auth\Authenticatable;

class AdminRegistrationAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $userEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, string $userEmail)
    {
        $this->userName = $userName;
        $this->userEmail = $userEmail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚨 New User Registration: APIs Hub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                <h2 style='color: #00A7F9;'>🚨 APIs Hub Centinela Alert</h2>
                <p>A new user has just registered on the platform.</p>
                <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>Name:</strong> {$this->userName}</p>
                    <p><strong>Email:</strong> {$this->userEmail}</p>
                    <p><strong>Time:</strong> " . now()->toDateTimeString() . "</p>
                </div>
                <p style='color: #888; font-size: 12px;'>This is an automated administrative email. Do not reply.</p>
            </div>
            "
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
