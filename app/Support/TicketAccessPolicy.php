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
        if ($user === null) {
            return false;
        }

        if ($ticket->requester_id === $user->id) {
            return self::isHeadOfTargetDepartment($ticket, $user);
        }

        return $user->hasElevatedRole() || self::isHeadOfTargetDepartment($ticket, $user);
    }

    public static function canWithdraw(Ticket $ticket, ?User $user): bool
    {
        return $user !== null
            && $ticket->requester_id === $user->id
            && in_array($ticket->status, ['open', 'in-progress'], true);
    }

    public static function canReroute(Ticket $ticket, ?User $user): bool
    {
        return $user !== null
            && $ticket->status !== 'closed'
            && ($user->hasElevatedRole() || self::isHeadOfTargetDepartment($ticket, $user));
    }

    public static function canRelease(Ticket $ticket, ?User $user): bool
    {
        return $user !== null
            && $ticket->status === 'in-progress'
            && ($ticket->assigned_to === $user->id || self::isHeadOfTargetDepartment($ticket, $user));
    }

    public static function canSetEffectiveness(Ticket $ticket, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($ticket->status === 'closed') {
            return blank($ticket->effectiveness) && $ticket->requester_id === $user->id;
        }

        if ($ticket->assigned_to === $user->id) {
            return true;
        }

        if ($ticket->requester_id === $user->id) {
            return self::isHeadOfTargetDepartment($ticket, $user);
        }

        if ($user->hasElevatedRole()) {
            return true;
        }

        return self::isHeadOfTargetDepartment($ticket, $user);
    }

    public static function canClose(Ticket $ticket, ?User $user): bool
    {
        return $ticket->status !== 'closed' && self::canSetEffectiveness($ticket, $user);
    }

    private static function isHeadOfTargetDepartment(Ticket $ticket, User $user): bool
    {
        $target = $ticket->effectiveTargetDepartment();

        if (!$target) {
            return false;
        }

        return $user->isHeadOfDepartment($target);
    }
}
