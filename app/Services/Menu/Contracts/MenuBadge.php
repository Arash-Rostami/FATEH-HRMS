<?php

namespace App\Services\Menu\Contracts;

interface MenuBadge
{
    public function getKey(): string;
    public function isActive(): bool;
    public function getTitle(): string;
    public function getBody(): string;
}