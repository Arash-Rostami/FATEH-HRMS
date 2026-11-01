<?php

namespace Tests\Feature\Providers;

use App\Models\ChannelMessage;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Message;
use App\Models\Read;
use App\Models\Reaction;
use App\Models\User;
use App\Providers\ModelCacheServiceProvider;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ModelCacheServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
        $this->app->register(ModelCacheServiceProvider::class);
    }

    public function test_saved_event_bumps_cache_version_for_normal_model(): void
    {
        $this->assertSame('0', ModelCacheVersion::version(Department::class));

        event('eloquent.saved: ' . Department::class, [new Department()]);

        $this->assertNotSame('0', ModelCacheVersion::version(Department::class));
    }

    public function test_deleted_event_bumps_cache_version_for_normal_model(): void
    {
        event('eloquent.deleted: ' . Department::class, [new Department()]);

        $this->assertNotSame('0', ModelCacheVersion::version(Department::class));
    }

    public function test_restored_event_bumps_cache_version_for_normal_model(): void
    {
        event('eloquent.restored: ' . Department::class, [new Department()]);

        $this->assertNotSame('0', ModelCacheVersion::version(Department::class));
    }

    public function test_saved_event_skips_model_implementing_skips_automatic_cache_versioning(): void
    {
        event('eloquent.saved: ' . User::class, [new User()]);

        $this->assertSame('0', ModelCacheVersion::version(User::class));
    }

    public function test_feed_interaction_hot_models_skip_the_automatic_version_bump(): void
    {
        // Read/Reaction/Comment are written on every feed read, reaction and comment — per-action hot
        // rows. Nothing keys a versioned cache on any of them (their invalidation rides
        // HasMenuState/PollDigestService instead), so the global listener's bump is a dead write per
        // action. Adding a versioned reader for one of these classes requires re-wiring its bumping —
        // this test is the tripwire that says the opt-out is deliberate. Message and ChannelMessage are
        // deliberately NOT in this list: the owner keeps the chat models on full automatic coverage even
        // though no reader keys on their versions either.
        foreach ([Read::class, Reaction::class, Comment::class] as $model) {
            event('eloquent.saved: ' . $model, [new $model()]);
            event('eloquent.deleted: ' . $model, [new $model()]);
            event('eloquent.restored: ' . $model, [new $model()]);

            $this->assertSame('0', ModelCacheVersion::version($model), "{$model} must stay opted out");
        }
    }

    public function test_chat_models_stay_on_full_automatic_version_coverage(): void
    {
        // Standing owner decision: the chat models are never opted out of the global listener,
        // even though no versioned reader keys on them today — their bump stays wired.
        foreach ([Message::class, ChannelMessage::class] as $model) {
            event('eloquent.saved: ' . $model, [new $model()]);

            $this->assertNotSame('0', ModelCacheVersion::version($model), "{$model} writes must keep bumping the version");
        }
    }
}