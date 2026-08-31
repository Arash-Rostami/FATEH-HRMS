<?php

namespace App\Services\Menu\Contracts;

use App\Models\User;

interface MenuEdge
{
    public function getKey(): string;

    public function triggers(): array;

    public function for($subject);

    public function title($subject, User $user): string;

    public function body($subject, User $user): string;
}