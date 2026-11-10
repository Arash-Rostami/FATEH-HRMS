<?php

namespace App\Support;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Models\User;

final class CorrespondenceAccessPolicy
{
    public static function isRegistry(?User $user): bool
    {
        return $user !== null
            && $user->isActive()
            && filter_var($user->getExtraValue('admin.correspondence_registry'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function isAuthor(?User $user, Correspondence $letter): bool
    {
        return $user !== null && (int) $letter->user_id === (int) $user->id;
    }

    public static function partyOf(Correspondence $letter, ?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        foreach ($letter->parties ?? [] as $party) {
            if (is_array($party) && (int) ($party['user_id'] ?? 0) === (int) $user->id) {
                return $party;
            }
        }

        return null;
    }

    public static function canView(?User $user, Correspondence $letter): bool
    {
        if (!self::live($user, $letter)) {
            return false;
        }

        if (self::isAuthor($user, $letter)) {
            return true;
        }

        $hidden = [...Correspondence::DRAFT_LIKE, Correspondence::STATUS_SAVED];

        return !in_array($letter->status, $hidden, true)
            && (in_array((int) $user->id, array_map('intval', (array) ($letter->recipients ?? [])), true)
                || self::partyOf($letter, $user) !== null);
    }

    public static function canEdit(?User $user, Correspondence $letter): bool
    {
        if (!self::live($user, $letter)) {
            return false;
        }

        $authorEditable = [...Correspondence::DRAFT_LIKE, Correspondence::STATUS_SAVED, Correspondence::STATUS_REJECTED];

        if (self::isAuthor($user, $letter) && in_array($letter->status, $authorEditable, true)) {
            return true;
        }

        return $letter->status === Correspondence::STATUS_SHARED
            && (self::partyOf($letter, $user)['role'] ?? null) === LetterForm::ROLE_APPROVER;
    }

    public static function canRegistry(?User $user, Correspondence $letter): bool
    {
        return self::live($user, $letter)
            && self::isRegistry($user)
            && in_array($letter->status, CorrespondencePresenter::REGISTRY_STATUSES, true)
            && !self::canView($user, $letter);
    }

    public static function canSteward(?User $user, Correspondence $letter): bool
    {
        return self::isAuthor($user, $letter)
            || (self::isRegistry($user) && ($letter->classification === Correspondence::CLASSIFICATION_NORMAL || self::canView($user, $letter)));
    }

    private static function live(?User $user, Correspondence $letter): bool
    {
        return $user !== null && $user->isActive() && !$letter->trashed();
    }
}
