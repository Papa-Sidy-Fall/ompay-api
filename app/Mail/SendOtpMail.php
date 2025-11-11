<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $type;
    public $expireAt;

    /**
     * Create a new message instance.
     */
    public function __construct($code, $type, $expireAt)
    {
        $this->code = $code;
        $this->type = $type;
        $this->expireAt = $expireAt;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->type) {
            'inscription' => 'Code de vérification pour votre inscription OmPay',
            'connexion' => 'Code de vérification pour votre connexion OmPay',
            'transaction' => 'Code de confirmation pour votre transaction OmPay',
            default => 'Code de vérification OmPay'
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'code' => $this->code,
                'expires_at' => $this->expireAt->format('d/m/Y à H:i'),
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
