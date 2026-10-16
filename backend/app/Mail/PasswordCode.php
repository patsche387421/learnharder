<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Code für „Passwort vergessen“ (E-025). Reiner Text wie RegistrationCode. */
class PasswordCode extends Mailable
{
    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Dein Code für ein neues Passwort');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.password-code', with: ['minutes' => config('accounts.code_minutes')]);
    }
}
