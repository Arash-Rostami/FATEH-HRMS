<?php

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Models\Post;
use App\Models\Profile;
use App\Models\User;
use App\Services\ContentSanitizerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionProperty;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->resetDbCharsetCache();
        DB::beginTransaction();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
        $this->resetDbCharsetCache();
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

    private function resetDbCharsetCache(): void
    {
        $prop = new ReflectionProperty(ContentSanitizerService::class, 'dbIsUtf8mb4');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_nudge_key_constant_is_posts_controller_nudge(): void
    {
        $this->assertSame('posts-controller:nudge', Post::NUDGE_KEY);
    }

    public function test_freshness_days_constant_is_thirty(): void
    {
        $this->assertSame(30, Post::FRESHNESS_DAYS);
    }

    public function test_body_set_mutator_strips_control_characters(): void
    {
        $post = new Post();
        $post->body = "hello\x00world";

        $this->assertSame('helloworld', $post->getAttributes()['body']);
    }

    public function test_body_set_mutator_passes_null_through_unchanged(): void
    {
        $post = new Post();
        $post->body = null;

        $this->assertNull($post->getAttributes()['body']);
    }

    public function test_body_set_mutator_passes_empty_string_through_unchanged(): void
    {
        $post = new Post();
        $post->body = '';

        $this->assertSame('', $post->getAttributes()['body']);
    }

    public function test_body_set_mutator_strips_control_characters_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'body' => "hello\x00world",
        ]);

        $this->assertSame('helloworld', $post->fresh()->body);
    }

    public function test_resolve_public_asset_url_returns_empty_string_for_null_or_blank(): void
    {
        $this->assertSame('', Post::resolvePublicAssetUrl(null));
        $this->assertSame('', Post::resolvePublicAssetUrl(''));
        $this->assertSame('', Post::resolvePublicAssetUrl('   '));
    }

    public function test_resolve_public_asset_url_passes_through_absolute_http_urls(): void
    {
        $this->assertSame('http://example.com/x.jpg', Post::resolvePublicAssetUrl('http://example.com/x.jpg'));
    }

    public function test_resolve_public_asset_url_passes_through_absolute_https_urls(): void
    {
        $this->assertSame('https://example.com/x.jpg', Post::resolvePublicAssetUrl('https://example.com/x.jpg'));
    }

    public function test_resolve_public_asset_url_passes_through_protocol_relative_urls(): void
    {
        $this->assertSame('//cdn.example.com/x.jpg', Post::resolvePublicAssetUrl('//cdn.example.com/x.jpg'));
    }

    public function test_resolve_public_asset_url_resolves_to_disk_url_when_file_exists_on_public_disk(): void
    {
        $disk = Storage::disk('public');
        $path = 'tests/_post_model_' . uniqid('', true) . '.txt';
        try {
            $disk->put($path, 'x');
            $this->assertStringContainsString($path, Post::resolvePublicAssetUrl($path));
        } finally {
            $disk->delete($path);
        }
    }

    public function test_resolve_public_asset_url_falls_back_to_asset_helper_for_missing_public_file(): void
    {
        $url = Post::resolvePublicAssetUrl('nonexistent/_post_' . uniqid('', true) . '.jpg');

        $this->assertStringContainsString('nonexistent', $url);
    }

    public function test_image_url_accessor_returns_empty_string_when_image_attribute_absent(): void
    {
        $post = new Post();

        $this->assertSame('', $post->imageUrl);
    }

    public function test_image_url_accessor_returns_empty_string_when_image_is_null(): void
    {
        $post = new Post();
        $post->image = null;

        $this->assertSame('', $post->imageUrl);
    }

    public function test_image_url_accessor_passes_through_absolute_https_image(): void
    {
        $post = new Post();
        $post->image = 'https://example.com/photo.jpg';

        $this->assertSame('https://example.com/photo.jpg', $post->imageUrl);
    }

    public function test_image_url_accessor_falls_back_to_asset_helper_for_missing_public_file(): void
    {
        $post = new Post();
        $post->image = 'nonexistent/_post_' . uniqid('', true) . '.jpg';

        $this->assertStringContainsString('nonexistent', $post->imageUrl);
    }

    public function test_image_url_accessor_caches_result_on_instance(): void
    {
        $post = new Post();
        $post->image = 'https://example.com/first.jpg';
        $first = $post->imageUrl;
        $post->image = 'https://example.com/second.jpg';

        $this->assertSame($first, $post->imageUrl);
    }

    public function test_is_fresh_returns_true_within_thirty_days(): void
    {
        $post = new Post();
        $post->created_at = now();

        $this->assertTrue($post->isFresh());
    }

    public function test_is_fresh_returns_true_just_inside_thirty_day_boundary(): void
    {
        $post = new Post();
        $post->created_at = now()->subDays(29);

        $this->assertTrue($post->isFresh());
    }

    public function test_is_fresh_returns_false_when_created_at_is_null(): void
    {
        $post = new Post();

        $this->assertFalse($post->isFresh());
    }

    public function test_is_fresh_returns_false_when_older_than_thirty_days(): void
    {
        $post = new Post();
        $post->created_at = now()->subDays(31);

        $this->assertFalse($post->isFresh());
    }

    public function test_factory_pinned_state_sets_pinned_true(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->pinned()->create(['user_id' => $user->id]);

        $this->assertSame(1, $post->fresh()->pinned);
    }

    public function test_factory_not_pinned_state_sets_pinned_false(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create(['user_id' => $user->id]);

        $this->assertSame(0, $post->fresh()->pinned);
    }

    public function test_factory_owned_by_state_sets_user_id(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->ownedBy($user)->create();

        $this->assertSame($user->id, $post->fresh()->user_id);
    }

    public function test_deleting_removes_image_file_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/image.jpg', 'content');
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'image' => 'posts/image.jpg',
        ]);

        $post->delete();

        Storage::disk('public')->assertMissing('posts/image.jpg');
    }

    public function test_deleting_with_no_image_does_not_error(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'image' => null,
        ]);

        $post->delete();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_cached_pins_returns_latest_pinned_post_and_auto_invalidates_on_save(): void
    {
        $pin = Post::factory()->create(['pinned' => 1]);

        $this->assertSame($pin->id, Post::cachedPins()->first()->id);

        $newer = Post::factory()->create(['pinned' => 1]);
        $this->assertSame($newer->id, Post::cachedPins()->first()->id, 'saving a newer pinned post must bump the Post version so cachedPins recomputes without a manual forget');
    }

    public function test_cached_pins_are_keyed_per_department_and_skip_inactive_pins(): void
    {
        $restricted = Post::factory()->create(['pinned' => 1, 'departments' => ['ZZ-PA']]);

        $this->assertSame($restricted->id, Post::cachedPins('ZZ-PA')->first()->id);
        $this->assertNotContains($restricted->id, Post::cachedPins('ZZ-PB')->pluck('id')->all());
        $this->assertNotContains($restricted->id, Post::cachedPins(null)->pluck('id')->all());

        $restricted->update(['active' => false]);

        $this->assertNotContains($restricted->id, Post::cachedPins('ZZ-PA')->pluck('id')->all(), 'deactivating a pin must bump the version so the department pin cache recomputes');
    }

    public function test_for_viewer_hides_inactive_and_other_department_posts_and_agrees_with_is_visible_to(): void
    {
        foreach (['ZZ-PA', 'ZZ-PB'] as $code) {
            Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        }
        $viewer = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $viewer->id, 'department_id' => 'ZZ-PA']);

        $public = Post::factory()->create(['departments' => null]);
        $own = Post::factory()->create(['departments' => ['ZZ-PA']]);
        $foreign = Post::factory()->create(['departments' => ['ZZ-PB']]);
        $inactive = Post::factory()->create(['departments' => [], 'active' => false]);

        $ids = Post::forViewer($viewer)->pluck('id')->all();

        $this->assertContains($public->id, $ids);
        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($inactive->id, $ids);

        foreach ([$public, $own, $foreign, $inactive] as $post) {
            $this->assertSame(in_array($post->id, $ids, true), Post::find($post->id)->isVisibleTo('ZZ-PA'));
        }
    }

    public function test_audience_users_are_limited_to_the_restricted_departments(): void
    {
        foreach (['ZZ-PA', 'ZZ-PB'] as $code) {
            Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        }
        $inside = User::factory()->create(['status' => 'active']);
        $outside = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $inside->id, 'department_id' => 'ZZ-PA']);
        Profile::factory()->create(['user_id' => $outside->id, 'department_id' => 'ZZ-PB']);

        $ids = Post::factory()->create(['departments' => ['ZZ-PA']])->audienceUsers()->pluck('id')->all();

        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_cached_item_auto_invalidates_on_delete(): void
    {
        $post = Post::factory()->create();

        $this->assertSame($post->id, Post::cachedItem($post->id)->id);

        $post->delete();
        $this->assertNull(Post::cachedItem($post->id), 'deleting the post must bump the version so cachedItem recomputes to null');
    }
}