<?php

namespace App\Models\Concerns\Hooks;

use App\Enums\FeedCategory;

trait HasFeedHooks
{
    protected static function bootHasFeedHooks(): void
    {
        static::saved(self::dropStaleVotesOnSaved(...));
        static::deleting(self::cleanRelationsAndFilesOnDeleting(...));
    }

    private static function dropStaleVotesOnSaved(self $feed)
    {
        if ($feed->wasRecentlyCreated || !$feed->wasChanged(['category', 'poll_options'])) {
            return;
        }

        $leftPoll = $feed->wasChanged('category') && $feed->category !== FeedCategory::Poll->value;
        $choices = fn($options) => static::extractPollSettings((array) $options)['choices'];

        if ($leftPoll || $choices($feed->getOriginal('poll_options')) !== $choices($feed->poll_options)) {
            $feed->polls()->delete();
        }
    }

    private static function cleanRelationsAndFilesOnDeleting(self $feed)
    {
        $feed->comments()->delete();
        $feed->reactions()->delete();
        $feed->polls()->delete();
        static::deleteStoredFiles($feed->media_paths);
    }
}
