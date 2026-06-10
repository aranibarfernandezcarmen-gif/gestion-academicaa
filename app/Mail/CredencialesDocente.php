<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesDocente extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $apellido,
        public string $registro,
        public string $ci
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Credenciales de Acceso - Sistema de Gestión Académica',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.credenciales-docente',
        );
    }
}
