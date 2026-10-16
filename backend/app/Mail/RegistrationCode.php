<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Code für die Registrierung (E-025). Reiner Text, kommt so auch in Microsoft 365 sauber an. */
class RegistrationCode extends Mailable
{
    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Dein Code für learnharder');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.registration-code', with: ['minutes' => config('accounts.code_minutes')]);
    }
}
