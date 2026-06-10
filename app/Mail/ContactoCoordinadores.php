<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactoCoordinadores extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $correoRemitente,
        public string $asunto,
        public string $descripcion
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contacto desde la web: ' . $this->asunto,
            replyTo: [$this->correoRemitente],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contacto-coordinadores',
        );
    }
}
