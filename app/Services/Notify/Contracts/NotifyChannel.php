<?php

namespace App\Services\Notify\Contracts;

use App\Models\User;
use App\Services\Notify\Message;

interface NotifyChannel
{
    public function key(): string;

    public function label(): string;

    public function isConfigured(): bool;

    public function addressFor(User $user): ?string;

    public function accepts(string $contact): bool;

    public function normalize(string $address): string;

    public function send(Message $message, string $address): bool;

    public function sendMany(Message $message, array $addresses): array;
}
