<?php

namespace App\Mail;

use App\Services\Notify\Message;
use App\Services\Notify\NotifyRegistry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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

    public function attachments(): array
    {
        return collect($this->message->attachments)
            ->filter(fn (mixed $path): bool => is_string($path) && is_file($path))
            ->map(fn (string $path): Attachment => Attachment::fromPath($path))
            ->values()
            ->all();
    }
}
