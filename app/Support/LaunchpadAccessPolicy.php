<?php

namespace App\Support;

use App\Models\Launchpad;
use App\Models\User;

final class LaunchpadAccessPolicy
{
    public static function canView(Launchpad $row, ?User $user): bool
    {
        return $user !== null
            && $user->isActive()
            && !$row->trashed()
            && ($row->isOwnedBy($user)
                || $user->hasElevatedRole()
                || ($row->isAudience($user) && in_array($row->status(), ['live', 'soon'], true)));
    }

    public static function canCreateIn(?User $user, string $dept): bool
    {
        $user?->loadMissing('profile');

        return $user !== null
            && $user->isVisibleOnBoard()
            && ($user->hasElevatedRole() || ($dept !== '' && $dept === (string) $user->profile?->department_id));
    }

    public static function canManage(Launchpad $row, ?User $user): bool
    {
        return $user !== null && ($row->isOwnedBy($user) || $user->hasElevatedRole());
    }

    public static function workspaceBlock(Launchpad $row, ?User $user): ?string
    {
        if (!self::canView($row, $user)) {
            return 'denied';
        }

        $status = $row->status();

        return in_array($status, ['inactive', 'scheduled', 'expired'], true) ? $status : null;
    }

    public static function token(Launchpad $row, User $user): string
    {
        $window = (int) config('launchpad.token_ttl_minutes') * 60;
        $expires = (intdiv(now()->timestamp, $window) + 2) * $window;

        return "{$user->id}-{$expires}-" . self::signature($row->id, $user->id, $expires);
    }

    public static function tokenUser(int $id, string $token): ?int
    {
        if (preg_match('/^([1-9][0-9]{0,9})-([1-9][0-9]{0,11})-([A-Za-z0-9_-]{43})$/', $token, $parts) !== 1) {
            return null;
        }

        [, $userId, $expires, $signature] = $parts;

        return (int) $expires >= now()->timestamp && hash_equals(self::signature($id, (int) $userId, (int) $expires), $signature)
            ? (int) $userId
            : null;
    }

    private static function signature(int $id, int $userId, int $expires): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', "launchpad|{$id}|{$userId}|{$expires}", (string) config('app.key'), true)), '+/', '-_'), '=');
    }
}
