<?php

namespace App\Mail;

use App\Services\Notify\Message;
use App\Services\Notify\NotifyRegistry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotifyMail extends Mailable
{
    public function __construct(public readonly Message $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->message->subject);
    }

    public function content(): Content
    {
        return new Content(view: NotifyRegistry::templateView($this->message->template), with: ['notify' => $this->message]);
    }
}
