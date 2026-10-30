<?php

namespace App\Models\Concerns\Hooks;

use App\Jobs\ReconcileNudge;
use App\Jobs\SyncProjectChannelMembershipJob;
use App\Models\Feed;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Project;
use App\Models\Report;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use RuntimeException;

trait HasProfileHooks
{
    protected static function bootHasProfileHooks(): void
    {
        static::deleting(self::deleteFilesAndBumpUserCacheOnDeleting(...));
        static::creating(self::guardDuplicateProfileOnCreating(...));
        static::saved(self::reconcileDepartmentMoveOnSaved(...));
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
                    ->each(fn($id) => ReconcileNudge::dispatch($nudgeKey, $class, $id));
            }

            Photo::where(fn($q) => collect($codes)->each(fn($code) => $q->orWhere('department_id', $code)->orWhereJsonContains('departments', $code)))
                ->pluck('id')
                ->each(fn($id) => ReconcileNudge::dispatch('gallery-controller:nudge', Photo::class, $id));
        }

        Ticket::where('status', 'open')
            ->where(fn($q) => collect($codes)->each(fn($code) => $q->orWhere(fn($sq) => $sq->targetedAt($code))))
            ->pluck('id')
            ->each(fn($id) => ReconcileNudge::dispatch('ths-controller:nudge', Ticket::class, $id));
    }
}
