<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Statt eines Codes, wenn es die Adresse schon gibt. Die Antwort der API verrät das nicht. */
class AlreadyRegistered extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Du hast schon ein Konto bei learnharder');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.already-registered');
    }
}
