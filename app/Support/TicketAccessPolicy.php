<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\User;

final class TicketAccessPolicy
{
    public static function canView(Ticket $ticket, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasElevatedRole()) {
            return true;
        }

        if ($ticket->requester_id === $user->id || $ticket->assigned_to === $user->id) {
            return true;
        }

        return self::isHeadOfTargetDepartment($ticket, $user);
    }

    public static function canReply(Ticket $ticket, ?User $user): bool
    {
        return self::canView($ticket, $user) && !Ticket::isClosingStatus($ticket->status);
    }

    public static function canAssign(Ticket $ticket, ?User $user): bool
    {
        return $user !== null && ($user->hasElevatedRole() || self::isHeadOfTargetDepartment($ticket, $user));
    }

    public static function canSetEffectiveness(Ticket $ticket, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasElevatedRole()) {
            return true;
        }

        return $ticket->assigned_to === $user->id || self::isHeadOfTargetDepartment($ticket, $user);
    }

    public static function canClose(Ticket $ticket, ?User $user): bool
    {
        return self::canSetEffectiveness($ticket, $user);
    }

    private static function isHeadOfTargetDepartment(Ticket $ticket, User $user): bool
    {
        $target = $ticket->targetDepartmentId ?: Ticket::defaultTargetDepartment();

        if (!$target) {
            return false;
        }

        return User::highestRankingInDepartment($target)?->is($user) ?? false;
    }
}
