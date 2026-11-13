<?php

namespace App\Support;

use App\Enums\Position;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Facades\Auth;

final class TopPositionPolicy
{
    public const LOCKED = [Position::Chairman->value, Position::Ceo->value];

    public static function isLocked(string|BackedEnum|null $position): bool
    {
        return in_array($position instanceof BackedEnum ? $position->value : $position, self::LOCKED, true);
    }

    public static function mayAssign(?User $actor = null): bool
    {
        $actor ??= Auth::user();

        return $actor === null || $actor->isDeveloper();
    }
}
