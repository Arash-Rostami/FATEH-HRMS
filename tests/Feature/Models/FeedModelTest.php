<?php

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Department;
use App\Models\Feed;
use App\Models\Poll;
use App\Models\Profile;
use App\Models\Reaction;
use App\Models\User;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        $this->resetSanitizerStaticCache();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
        $this->resetSanitizerStaticCache();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }

    private function resetSanitizerStaticCache(): void
    {
        $prop = new \ReflectionProperty(ContentSanitizerService::class, 'dbIsUtf8mb4');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_nudge_key_constant_is_feeds_nudge(): void
    {
        $this->assertSame('feeds:nudge', Feed::NUDGE_KEY);
    }

    public function test_image_extensions_constant_lists_supported_image_types(): void
    {
        $this->assertSame(['jpg', 'jpeg', 'png', 'gif', 'webp'], Feed::IMAGE_EXTENSIONS);
    }

    public function test_video_extensions_constant_lists_supported_video_types(): void
    {
        $this->assertSame(['mp4', 'mov', 'webm', 'avi', 'mkv'], Feed::VIDEO_EXTENSIONS);
    }

    public function test_extract_poll_settings_returns_structured_when_first_token_is_mode(): void
    {
        $settings = Feed::extractPollSettings(['single', 'true', 'false', 'A', 'B']);

        $this->assertSame('single', $settings['mode']);
        $this->assertTrue($settings['comments']);
        $this->assertFalse($settings['reactions']);
        $this->assertSame(['A', 'B'], $settings['choices']);
    }

    public function test_extract_poll_settings_recognizes_multiple_mode(): void
    {
        $settings = Feed::extractPollSettings(['multiple', '1', '0', 'X', 'Y']);

        $this->assertSame('multiple', $settings['mode']);
        $this->assertTrue($settings['comments']);
        $this->assertFalse($settings['reactions']);
        $this->assertSame(['X', 'Y'], $settings['choices']);
    }

    public function test_extract_poll_settings_defaults_when_count_below_three(): void
    {
        $settings = Feed::extractPollSettings(['A', 'B']);

        $this->assertSame('single', $settings['mode']);
        $this->assertTrue($settings['comments']);
        $this->assertTrue($settings['reactions']);
        $this->assertSame(['A', 'B'], $settings['choices']);
    }

    public function test_extract_poll_settings_defaults_when_first_token_is_not_a_mode(): void
    {
        $settings = Feed::extractPollSettings(['foo', 'true', 'false', 'A']);

        $this->assertSame('single', $settings['mode']);
        $this->assertTrue($settings['comments']);
        $this->assertTrue($settings['reactions']);
        $this->assertSame(['foo', 'true', 'false', 'A'], $settings['choices']);
    }

    public function test_extract_poll_settings_comments_and_reactions_accept_truthy_scalars(): void
    {
        $truthy = Feed::extractPollSettings(['single', 1, true, 'A']);
        $this->assertTrue($truthy['comments']);
        $this->assertTrue($truthy['reactions']);

        $falsy = Feed::extractPollSettings(['single', '0', 'false', 'A']);
        $this->assertFalse($falsy['comments']);
        $this->assertFalse($falsy['reactions']);
    }

    public function test_extract_poll_settings_reindexes_choices_with_array_values(): void
    {
        $settings = Feed::extractPollSettings(['single', '1', '1', 5 => 'A', 10 => 'B']);

        $this->assertSame(['A', 'B'], $settings['choices']);
        $this->assertSame([0 => 'A', 1 => 'B'], $settings['choices']);
    }

    public function test_extract_poll_settings_coerces_nested_array_choice_to_string_without_reindexing(): void
    {
        $settings = Feed::extractPollSettings(['single', '1', '1', 'A', ['x', 'y'], 'C']);

        $this->assertSame(['A', 'x, y', 'C'], $settings['choices']);
    }

    public function test_content_set_mutator_sanitizes_input(): void
    {
        $feed = new Feed();
        $feed->content = "hello\x00world";

        $this->assertSame('helloworld', $feed->getAttributes()['content']);
    }

    public function test_content_set_mutator_passes_null_through_unchanged(): void
    {
        $feed = new Feed();
        $feed->content = null;

        $this->assertNull($feed->getAttributes()['content']);
    }

    public function test_images_accessor_filters_media_paths_by_image_extensions(): void
    {
        $feed = new Feed();
        $feed->media_paths = ['pic.JPG', 'clip.mp4', 'note.txt', 'photo.png'];

        $this->assertSame(['pic.JPG', 'photo.png'], $feed->images);
    }

    public function test_videos_accessor_filters_media_paths_by_video_extensions(): void
    {
        $feed = new Feed();
        $feed->media_paths = ['pic.jpg', 'clip.MP4', 'movie.webm', 'note.txt'];

        $this->assertSame(['clip.MP4', 'movie.webm'], $feed->videos);
    }

    public function test_images_and_videos_accessors_return_empty_when_media_paths_unset(): void
    {
        $feed = new Feed();

        $this->assertSame([], $feed->images);
        $this->assertSame([], $feed->videos);
    }

    public function test_media_urls_accessor_maps_each_path_to_resolved_url(): void
    {
        $feed = new Feed();
        $feed->media_paths = ['foo.jpg', ''];

        $urls = $feed->mediaUrls;

        $this->assertCount(1, $urls);
        $this->assertStringContainsString('foo.jpg', $urls[0]);
    }

    public function test_poll_choices_returns_resolved_settings_choices(): void
    {
        $feed = new Feed();
        $feed->poll_options = ['single', '1', '1', 'A', 'B'];

        $this->assertSame(['A', 'B'], $feed->pollChoices());
    }

    public function test_poll_settings_returns_settings_without_choices_key(): void
    {
        $feed = new Feed();
        $feed->poll_options = ['multiple', '1', '0', 'A'];

        $settings = $feed->pollSettings();

        $this->assertSame('multiple', $settings['mode']);
        $this->assertArrayNotHasKey('choices', $settings);
    }

    public function test_resolved_poll_settings_memoizes_into_private_cache(): void
    {
        $feed = new Feed();
        $feed->poll_options = ['single', '1', '1', 'A'];

        $cacheProp = new \ReflectionProperty(Feed::class, 'pollSettingsCache');
        $cacheProp->setAccessible(true);

        $first = $feed->pollChoices();
        $cached = $cacheProp->getValue($feed);

        $this->assertSame(['A'], $first);
        $this->assertSame(['A'], $cached['choices']);

        $feed->poll_options = ['multiple', '0', '0', 'Z'];
        $second = $feed->pollChoices();

        $this->assertSame(['A'], $second, 'memoized cache must ignore later poll_options mutation');
    }

    public function test_is_fresh_returns_true_within_thirty_days(): void
    {
        $feed = new Feed();
        $feed->created_at = now();

        $this->assertTrue($feed->isFresh());
    }

    public function test_is_fresh_returns_false_when_created_at_is_null(): void
    {
        $feed = new Feed();

        $this->assertFalse($feed->isFresh());
    }

    public function test_is_fresh_returns_false_when_older_than_thirty_days(): void
    {
        $feed = new Feed();
        $feed->created_at = now()->subDays(31);

        $this->assertFalse($feed->isFresh());
    }

    public function test_media_paths_cast_round_trips_array_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'media_paths' => ['a.jpg', 'b.mp4'],
        ]);

        $this->assertSame(['a.jpg', 'b.mp4'], $feed->fresh()->media_paths);
    }

    public function test_poll_options_cast_round_trips_array_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'poll_options' => ['single', '1', '1', 'A', 'B'],
        ]);

        $this->assertSame(['single', '1', '1', 'A', 'B'], $feed->fresh()->poll_options);
    }

    public function test_scope_by_category_filters_to_matching_category(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $kept = Feed::factory()->create(['user_id' => $user->id, 'category' => 'news']);
        $other = Feed::factory()->create(['user_id' => $user->id, 'category' => 'sports']);

        $ids = Feed::byCategory('news')->pluck('id')->all();

        $this->assertContains($kept->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_poll_results_counts_votes_by_option_index(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $voterA = User::factory()->create(['status' => 'active']);
        $voterB = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        $feed->polls()->create(['user_id' => $voterA->id, 'option_index' => 0]);
        $feed->polls()->create(['user_id' => $voterA->id, 'option_index' => 1]);
        $feed->polls()->create(['user_id' => $voterB->id, 'option_index' => 0]);

        $results = $feed->pollResults();

        $this->assertSame(3, $results['total']);
        $this->assertSame(2, $results['counts'][0]);
        $this->assertSame(1, $results['counts'][1]);
    }

    public function test_poll_results_returns_empty_when_no_votes(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        $results = $feed->pollResults();

        $this->assertSame(0, $results['total']);
        $this->assertSame([], $results['counts']);
    }

    public function test_deleting_feed_cascades_to_comments_reactions_and_polls(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        $comment = Comment::factory()->create(['feed_id' => $feed->id, 'user_id' => $user->id]);
        $reaction = Reaction::factory()->create(['feed_id' => $feed->id, 'user_id' => $user->id, 'emoji' => '👍']);
        $poll = Poll::factory()->create(['feed_id' => $feed->id, 'user_id' => $user->id, 'option_index' => 0]);

        $feed->delete();

        $this->assertNull(Comment::find($comment->id));
        $this->assertNull(Reaction::find($reaction->id));
        $this->assertNull(Poll::find($poll->id));
    }

    public function test_deleting_removes_media_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('feeds/a.jpg', 'content');
        Storage::disk('public')->put('feeds/b.mp4', 'content');
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'media_paths' => ['feeds/a.jpg', 'feeds/b.mp4'],
        ]);

        $feed->delete();

        Storage::disk('public')->assertMissing('feeds/a.jpg');
        Storage::disk('public')->assertMissing('feeds/b.mp4');
    }

    public function test_deleting_with_no_media_paths_does_not_error(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active']);
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'media_paths' => null,
        ]);

        $feed->delete();

        $this->assertDatabaseMissing('feeds', ['id' => $feed->id]);
    }

    private function audienceViewer(string $code): User
    {
        Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code]);

        return $user;
    }

    public function test_for_viewer_hides_inactive_and_other_department_feeds_and_agrees_with_is_visible_to(): void
    {
        $viewer = $this->audienceViewer('ZZ-FA');
        $this->audienceViewer('ZZ-FB');

        $public = Feed::factory()->create(['departments' => null]);
        $emptyPublic = Feed::factory()->create(['departments' => []]);
        $own = Feed::factory()->create(['departments' => ['ZZ-FA', 'ZZ-FB']]);
        $foreign = Feed::factory()->create(['departments' => ['ZZ-FB']]);
        $inactive = Feed::factory()->create(['departments' => null, 'active' => false]);
        $inactiveOwn = Feed::factory()->create(['departments' => ['ZZ-FA'], 'active' => false]);

        $ids = Feed::forViewer($viewer)->pluck('id')->all();

        $this->assertContains($public->id, $ids);
        $this->assertContains($emptyPublic->id, $ids);
        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertNotContains($inactiveOwn->id, $ids);

        foreach ([$public, $emptyPublic, $own, $foreign, $inactive, $inactiveOwn] as $feed) {
            $this->assertSame(in_array($feed->id, $ids, true), Feed::find($feed->id)->isVisibleTo('ZZ-FA'));
        }
        $this->assertContains($inactive->id, Feed::inactive()->pluck('id')->all());
        $this->assertNotContains($inactive->id, Feed::active()->pluck('id')->all());
    }

    public function test_for_viewer_without_a_profile_department_sees_only_public_active_feeds(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $public = Feed::factory()->create(['departments' => null]);
        $restricted = Feed::factory()->create(['departments' => ['ZZ-FA']]);

        $ids = Feed::forViewer($user)->pluck('id')->all();

        $this->assertContains($public->id, $ids);
        $this->assertNotContains($restricted->id, $ids);
        $this->assertFalse(Feed::find($restricted->id)->isVisibleTo(null));
    }

    public function test_audience_users_are_all_active_users_when_public_and_only_department_members_when_restricted(): void
    {
        $inside = $this->audienceViewer('ZZ-FA');
        $outside = $this->audienceViewer('ZZ-FB');

        $public = Feed::factory()->create(['departments' => null]);
        $restricted = Feed::factory()->create(['departments' => ['ZZ-FA']]);

        $publicIds = $public->audienceUsers()->pluck('id')->all();
        $restrictedIds = $restricted->audienceUsers()->pluck('id')->all();

        $this->assertContains($inside->id, $publicIds);
        $this->assertContains($outside->id, $publicIds);
        $this->assertContains($inside->id, $restrictedIds);
        $this->assertNotContains($outside->id, $restrictedIds);
    }
}