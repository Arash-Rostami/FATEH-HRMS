<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Posts;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PostsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
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

    public function test_posts_render_successfully_for_authenticated_user()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Posts::class)
            ->assertStatus(200)
            ->assertHasNoErrors();
    }

    public function test_posts_render_successfully_for_guest()
    {
        Livewire::test(Posts::class)
            ->assertStatus(200)
            ->assertHasNoErrors();
    }

    public function test_pins_returns_only_pinned_posts()
    {
        $pinnedPost = Post::factory()->pinned()->create();
        $regularPost = Post::factory()->notPinned()->create();

        $pins = Livewire::test(Posts::class)->instance()->pins;

        $this->assertTrue($pins->every(fn($p) => (int)$p->pinned === 1));
        $this->assertFalse($pins->contains('id', $regularPost->id));
    }

    public function test_pins_computed_returns_at_most_one_pinned_post()
    {
        Post::factory()->pinned()->create();
        Post::factory()->pinned()->create();

        $pins = Livewire::test(Posts::class)->instance()->pins;

        $this->assertSame(1, $pins->count());
    }

    public function test_posts_excludes_pinned_posts_from_the_grid()
    {
        $pinnedPost = Post::factory()->pinned()->create();
        $pinnedPost->created_at = now();
        $pinnedPost->save();
        $regularPost = Post::factory()->notPinned()->create();
        $regularPost->created_at = now()->subSecond();
        $regularPost->save();

        $posts = Livewire::test(Posts::class)->instance()->posts;

        $this->assertFalse($posts->contains('id', $pinnedPost->id));
        $this->assertTrue($posts->contains('id', $regularPost->id));
    }

    public function test_posts_orders_by_created_at_descending()
    {
        $newer = Post::factory()->notPinned()->create();
        $newer->created_at = now()->subSecond();
        $newer->save();
        $older = Post::factory()->notPinned()->create();
        $older->created_at = now()->subSeconds(2);
        $older->save();

        $posts = Livewire::test(Posts::class)->instance()->posts;

        $newerPos = $posts->search(fn($p) => $p->is($newer));
        $olderPos = $posts->search(fn($p) => $p->is($older));
        $this->assertNotFalse($newerPos);
        $this->assertNotFalse($olderPos);
        $this->assertLessThan($olderPos, $newerPos);
    }

    public function test_pagination_caps_grid_at_page_times_three()
    {
        Post::factory()->count(10)->notPinned()->create();

        $testable = Livewire::test(Posts::class);
        $this->assertSame(3, $testable->instance()->posts->count());

        $testable->call('loadMore');
        $this->assertSame(6, $testable->instance()->posts->count());
    }

    public function test_loadMore_increments_page_property()
    {
        Post::factory()->notPinned()->create();

        Livewire::test(Posts::class)
            ->assertSet('page', 1)
            ->call('loadMore')
            ->assertSet('page', 2);
    }

    public function test_total_posts_returns_count_of_all_posts()
    {
        $before = Post::count();
        Post::factory()->count(4)->notPinned()->create();
        Post::factory()->pinned()->create();

        Livewire::test(Posts::class)
            ->assertSet('totalPosts', $before + 5);
    }

    public function test_select_post_sets_selected_post_from_database()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('selectPost', $post->id)
            ->assertSet('selectedPost.id', $post->id);
    }

    public function test_select_post_dispatches_open_post_panel_event()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('selectPost', $post->id)
            ->assertDispatched('open-post-panel');
    }

    public function test_select_post_marks_post_as_read_for_authenticated_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();
        $this->insertPostNudge($user, $post->id);

        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('selectPost', $post->id)
            ->assertHasNoErrors();

        $this->assertNotNull(
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('data->item_id', $post->id)
                ->value('read_at')
        );
    }

    public function test_select_post_does_not_mark_read_for_guest()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();
        $this->insertPostNudge($user, $post->id);

        Livewire::test(Posts::class)
            ->call('selectPost', $post->id)
            ->assertSet('selectedPost.id', $post->id);

        $this->assertNull(
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('data->item_id', $post->id)
                ->value('read_at')
        );
    }

    public function test_select_post_with_nonexistent_id_leaves_selected_post_null()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('selectPost', 999999)
            ->assertSet('selectedPost', null)
            ->assertDispatched('open-post-panel');
    }

    public function test_seen_ids_returns_empty_collection_for_guest()
    {
        $seenIds = Livewire::test(Posts::class)->instance()->seenIds;

        $this->assertTrue($seenIds->isEmpty());
    }

    public function test_seen_ids_reflects_read_notifications_for_authenticated_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();
        $this->insertPostNudge($user, $post->id, true);

        $seenIds = Livewire::actingAs($user)->test(Posts::class)->instance()->seenIds;

        $this->assertTrue($seenIds->has($post->id));
    }

    public function test_focus_record_delegates_to_select_post()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('focusRecord', $post->id)
            ->assertSet('selectedPost.id', $post->id);
    }

    public function test_mount_with_open_query_param_focuses_record()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::actingAs($user)
            ->test(Posts::class, ['open' => $post->id])
            ->assertSet('selectedPost.id', $post->id)
            ->assertDispatched('record-focus');
    }

    public function test_is_focusing_reflects_open_query_param_state()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        $component = Livewire::actingAs($user)->test(Posts::class, ['open' => $post->id])->instance();
        $this->assertTrue($component->isFocusing());

        $component->clearFocus();
        $this->assertNull($component->open);
        $this->assertFalse($component->isFocusing());
    }

    private function insertPostNudge(User $user, int $postId, bool $read = false): void
    {
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'database',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'menu_key' => Post::NUDGE_KEY,
                'item_id' => $postId,
                'title' => 'test nudge',
                'body' => 'test body',
            ]),
            'read_at' => $read ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_toggle_view_sets_and_persists_view_choice(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->test(Posts::class)->assertSet('view', 'card')->call('toggleView', 'list')->assertSet('view', 'list');
        Livewire::actingAs($user)->test(Posts::class)->assertSet('view', 'list');
    }

    public function test_toggle_view_ignores_unsupported_value(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->test(Posts::class)
            ->call('toggleView', 'list')->assertSet('view', 'list')
            ->call('toggleView', 'grid')->assertSet('view', 'list');
    }
}