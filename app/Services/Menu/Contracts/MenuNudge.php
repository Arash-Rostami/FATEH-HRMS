<?php

namespace App\Services\Menu\Contracts;

use App\Models\User;

interface MenuNudge
{
    public function getKey(): string;

    public function triggers(): array;

    public function show($subject, User $user): bool;

    public function for($subject);

    public function title($subject, User $user): string;

    public function body($subject, User $user): string;

    public function refresh(): bool;
}