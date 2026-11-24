<?php

namespace App\Support;

use App\Enums\ChannelRole;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ChannelAccessPolicy
{
    public const OWNER = 'owner';

    public static function role(?Channel $channel, ?User $user): ?string
    {
        if ($channel === null || $user === null) {
            return null;
        }

        if ($channel->owner_id !== null && (int) $channel->owner_id === (int) $user->id) {
            return self::OWNER;
        }

        $role = array_key_exists('viewer_role', $channel->getAttributes())
            ? $channel->getAttributes()['viewer_role']
            : DB::table('channel_members')
                ->where('channel_id', $channel->id)
                ->where('user_id', $user->id)
                ->value('role');

        return $role === null ? null : (ChannelRole::tryFrom($role)?->value ?? ChannelRole::Viewer->value);
    }

    private static function allowsPost(?string $role): bool
    {
        return $role !== null && $role !== ChannelRole::Viewer->value;
    }

    public static function isOwner(?Channel $channel, ?User $user): bool
    {
        return self::role($channel, $user) === self::OWNER;
    }

    public static function isAdmin(?Channel $channel, ?User $user): bool
    {
        return self::role($channel, $user) === ChannelRole::Admin->value;
    }

    public static function canManageMembers(Channel $channel, ?User $user): bool
    {
        return in_array(self::role($channel, $user), [self::OWNER, ChannelRole::Admin->value], true);
    }

    public static function canPost(?Channel $channel, ?User $user): bool
    {
        return self::allowsPost(self::role($channel, $user));
    }

    public static function canChangeType(Channel $channel, ?User $user): bool
    {
        return self::canManageMembers($channel, $user) && !$channel->isProjectLinked();
    }

    public static function canManageAdmins(Channel $channel, ?User $user): bool
    {
        return self::isOwner($channel, $user) && !$channel->isProjectLinked();
    }

    public static function canSetKey(Channel $channel, ?User $user): bool
    {
        return self::isOwner($channel, $user);
    }

    public static function canDelete(Channel $channel, ?User $user): bool
    {
        return self::isOwner($channel, $user);
    }

    public static function canLeave(Channel $channel, int $userId): bool
    {
        return $channel->owner_id === null || (int) $channel->owner_id !== $userId;
    }

    public static function abilities(?Channel $channel, ?User $user): array
    {
        $role = self::role($channel, $user);
        $owner = $role === self::OWNER;
        $manage = $owner || $role === ChannelRole::Admin->value;
        $free = $manage && !$channel->isProjectLinked();

        return [
            'post' => self::allowsPost($role),
            'manageMembers' => $manage,
            'changeType' => $free,
            'manageAdmins' => $owner && $free,
            'setKey' => $owner,
            'delete' => $owner,
            'leave' => $channel !== null && $user !== null && self::canLeave($channel, (int) $user->id),
        ];
    }
}
