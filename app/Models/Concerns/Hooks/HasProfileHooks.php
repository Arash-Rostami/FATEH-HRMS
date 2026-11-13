<?php

namespace App\Models\Concerns\Hooks;

use App\Jobs\ReconcileNudgeJob;
use App\Jobs\SyncProjectChannelMembershipJob;
use App\Models\Feed;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Project;
use App\Models\Report;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Support\TopPositionPolicy;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait HasProfileHooks
{
    protected static function bootHasProfileHooks(): void
    {
        static::deleting(self::guardTopPositionOnDeleting(...));
        static::deleting(self::deleteFilesAndBumpUserCacheOnDeleting(...));
        static::creating(self::guardDuplicateProfileOnCreating(...));
        static::saving(self::guardTopPositionOnSaving(...));
        static::saved(self::reconcileDepartmentMoveOnSaved(...));
    }

    private static function guardTopPositionOnSaving(self $profile)
    {
        if (TopPositionPolicy::mayAssign() || !$profile->isDirty(['position', 'user_id'])) {
            return;
        }

        $persisted = self::persistedPosition($profile);
        $positionTouched = $profile->isDirty('position')
            && (TopPositionPolicy::isLocked($profile->position) || TopPositionPolicy::isLocked($persisted));
        $ownerMoved = $profile->exists && $profile->isDirty('user_id') && TopPositionPolicy::isLocked($persisted);

        if ($positionTouched || $ownerMoved) {
            throw ValidationException::withMessages(['position' => __('resources/profile/strings.validation.position_locked')]);
        }
    }

    private static function guardTopPositionOnDeleting(self $profile)
    {
        if (!TopPositionPolicy::mayAssign() && TopPositionPolicy::isLocked(self::persistedPosition($profile))) {
            throw ValidationException::withMessages(['position' => __('resources/profile/strings.validation.position_locked')]);
        }
    }

    private static function persistedPosition(self $profile): ?string
    {
        return $profile->exists ? static::query()->whereKey($profile->getKey())->lockForUpdate()->value('position') : null;
    }

    private static function deleteFilesAndBumpUserCacheOnDeleting(self $profile)
    {
        static::deleteStoredFiles($profile->image);
        static::deleteStoredFiles($profile->attachments);
        ModelCacheVersion::bump(User::class);
    }

    private static function guardDuplicateProfileOnCreating(self $profile)
    {
        if (static::where('user_id', $profile->user_id)->exists()) {
            throw new RuntimeException(__('resources/profile/strings.validation.duplicate'));
        }
    }

    private static function reconcileDepartmentMoveOnSaved(self $profile)
    {
        $moved = $profile->wasChanged('department_id');

        if (!$moved && !$profile->wasChanged('position')) {
            return;
        }

        ModelCacheVersion::bump(User::class);

        $codes = array_filter([$profile->getOriginal('department_id'), $profile->department_id]);

        if (!$codes) {
            return;
        }

        if ($moved) {
            Project::whereNotNull('channel_id')
                ->where(fn($q) => collect($codes)->each(fn($code) => $q->orWhereJsonContains('departments', $code)))
                ->pluck('id')
                ->each(fn($id) => SyncProjectChannelMembershipJob::dispatch($id));
        }

        if ($moved) {
            foreach ([Post::class => Post::NUDGE_KEY, Feed::class => Feed::NUDGE_KEY, Report::class => 'reports-controller:nudge'] as $class => $nudgeKey) {
                $class::whereNotNull('departments')
                    ->where(fn($q) => collect($codes)->each(fn($code) => $q->orWhereJsonContains('departments', $code)))
                    ->pluck('id')
                    ->each(fn($id) => ReconcileNudgeJob::dispatch($nudgeKey, $class, $id));
            }

            Photo::where(fn($q) => collect($codes)->each(fn($code) => $q->orWhere('department_id', $code)->orWhereJsonContains('departments', $code)))
                ->pluck('id')
                ->each(fn($id) => ReconcileNudgeJob::dispatch('gallery-controller:nudge', Photo::class, $id));
        }

        Ticket::where('status', 'open')
            ->where(fn($q) => collect($codes)->each(fn($code) => $q->orWhere(fn($sq) => $sq->targetedAt($code))))
            ->pluck('id')
            ->each(fn($id) => ReconcileNudgeJob::dispatch('ths-controller:nudge', Ticket::class, $id));
    }
}
