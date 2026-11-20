<?php

namespace App\Models\Concerns\Hooks;

use App\Enums\UserType;
use App\Services\Cache\ModelCacheVersion;
use App\Models\Channel;
use App\Models\Profile;
use App\Models\Project;
use App\Support\TopPositionPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait HasUserHooks
{
    protected static function bootHasUserHooks(): void
    {
        static::created(self::bumpCacheVersionOnCreated(...));
        static::saving(self::guardHolderAccountOnSaving(...));
        static::saving(self::guardTerminatedReactivationOnSaving(...));
        static::saving(self::guardOwnedChannelsOnDeactivating(...));
        static::saving(self::clearEmailTrustOnEmailChange(...));
        static::deleting(self::guardHolderAccountOnDeleting(...));
        static::saved(self::syncCachesAndReleaseWorkOnSaved(...));
        static::deleted(self::bumpCacheVersionOnDeleted(...));
    }

    private static function guardHolderAccountOnDeleting(self $user)
    {
        $actor = Auth::user();

        if ($actor === null || $actor->isDeveloper()) {
            return;
        }

        if (self::isPersistedHolder($user, self::persistedRole($user))) {
            throw ValidationException::withMessages(['email' => __('resources/user/strings.validation.holder_locked')]);
        }
    }

    private static function persistedRole(self $user): ?string
    {
        return $user->exists ? static::query()->whereKey($user->getKey())->lockForUpdate()->value('role') : null;
    }

    private static function isPersistedHolder(self $user, ?string $role): bool
    {
        return $role === 'developer'
            || TopPositionPolicy::isLocked(Profile::query()->where('user_id', $user->getKey())->lockForUpdate()->value('position'));
    }

    private static function guardHolderAccountOnSaving(self $user)
    {
        if (!$user->isDirty(['email', 'password', 'role', 'status', 'type', 'extra'])) {
            return;
        }

        $actor = Auth::user();

        if ($actor === null || $actor->getOriginal('role') === 'developer') {
            return;
        }

        $role = self::persistedRole($user);

        if ($user->isDirty('role') && ($user->role === 'developer' || $role === 'developer')) {
            self::rejectHolderChange($user, 'role');
        }

        $isHolder = $user->exists && $actor->id !== $user->id && self::isPersistedHolder($user, $role);

        if ($isHolder && $user->isDirty(['email', 'password', 'role', 'status', 'type', 'extra'])) {
            self::rejectHolderChange($user, 'email');
        }
    }

    private static function guardTerminatedReactivationOnSaving(self $user)
    {
        if (!$user->exists || !$user->isDirty('status') || $user->status !== 'active') {
            return;
        }

        if (Profile::query()->where('user_id', $user->getKey())->where('employment_status', 'terminated')->exists()) {
            $user->setRawAttributes($user->getRawOriginal());

            throw ValidationException::withMessages(['data.status' => __('resources/user/strings.validation.terminated_locked')]);
        }
    }

    private static function guardOwnedChannelsOnDeactivating(self $user)
    {
        $wasVisible = $user->exists
            && $user->getOriginal('status') === 'active'
            && $user->getOriginal('type') !== UserType::Guest->value;

        $leaving = $wasVisible && ($user->status !== 'active' || $user->type === UserType::Guest->value);

        if ($leaving && Channel::query()
                ->where('owner_id', $user->getKey())
                ->whereNotIn('id', Project::withTrashed()->whereNotNull('channel_id')->select('channel_id'))
                ->exists()) {
            $field = $user->isDirty('status') ? 'status' : 'type';

            $user->setRawAttributes($user->getRawOriginal());

            throw ValidationException::withMessages(["data.{$field}" => __('resources/user/strings.validation.owns_channels')]);
        }
    }

    private static function clearEmailTrustOnEmailChange(self $user)
    {
        if ($user->exists && $user->isDirty('email') && !$user->isDirty('email_verified_at')) {
            $user->email_verified_at = null;
        }
    }

    private static function rejectHolderChange(self $user, string $field): never
    {
        $user->setRawAttributes($user->getRawOriginal());

        throw ValidationException::withMessages([$field => __('resources/user/strings.validation.holder_locked')]);
    }

    private static function bumpCacheVersionOnCreated()
    {
        return static::bumpCacheVersion();
    }

    private static function syncCachesAndReleaseWorkOnSaved(self $user)
    {
        if ($user->wasChanged(['name', 'role', 'status', 'type'])) {
            static::bumpCacheVersion();
        }

        if ($user->wasChanged(['presence', 'focus_until'])) {
            ModelCacheVersion::bump(static::PRESENCE_VERSION_KEY);
        }

        self::forgetDynamicGroupCache();

        $leavingBoard = !$user->wasRecentlyCreated && $user->wasChanged(['status', 'type'])
            && $user->getOriginal('status') === 'active'
            && ($user->status !== 'active' || $user->type === UserType::Guest->value);

        if ($leavingBoard) {
            $user->cancelFutureReservations();
        }

        if ($leavingBoard && $user->getOriginal('type') !== UserType::Guest->value) {
            $user->releaseFromProjectWork();
            $user->reconcileDepartmentTickets();
        }
    }

    private static function bumpCacheVersionOnDeleted()
    {
        static::bumpCacheVersion();
        self::forgetDynamicGroupCache();
    }
}
