<?php

namespace App\Services\Notify\Channels;

use App\Mail\NotifyMail;
use App\Models\User;
use App\Services\Notify\Contracts\NotifyChannel;
use App\Services\Notify\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailChannel implements NotifyChannel
{
    public function key(): string
    {
        return 'mail';
    }

    public function label(): string
    {
        return 'ایمیل';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function addressFor(User $user): ?string
    {
        return $user->email;
    }

    public function accepts(string $contact): bool
    {
        return filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function normalize(string $address): string
    {
        return mb_strtolower(trim($address));
    }

    public function send(Message $message, string $address): bool
    {
        Mail::to($address)->send(new NotifyMail($message));

        return true;
    }

    public function sendMany(Message $message, array $addresses): array
    {
        $results = [];

        foreach ($addresses as $address) {
            try {
                $this->send($message, $address);
                $results[$address] = null;
            } catch (Throwable $e) {
                report($e);
                $results[$address] = __('resources/notify_rule/strings.batch.mail_failed');
            }
        }

        return $results;
    }
}
