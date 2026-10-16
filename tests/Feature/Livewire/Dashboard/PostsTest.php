<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Posts;
use App\Models\Department;
use App\Models\Post;
use App\Models\Profile;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
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
        Livewire::withoutLazyLoading();
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

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Posts::class)
            ->assertStatus(200)
            ->assertHasNoErrors();
    }

    public function test_posts_render_successfully_for_guest()
    {
        Livewire::withoutLazyLoading();
        Livewire::test(Posts::class)
            ->assertStatus(200)
            ->assertHasNoErrors();
    }

    public function test_pins_returns_only_pinned_posts()
    {
        $pinnedPost = Post::factory()->pinned()->create();
        $regularPost = Post::factory()->notPinned()->create();

        Livewire::withoutLazyLoading();
        $pins = Livewire::test(Posts::class)->instance()->pins;

        $this->assertTrue($pins->every(fn($p) => (int)$p->pinned === 1));
        $this->assertFalse($pins->contains('id', $regularPost->id));
    }

    public function test_pins_computed_returns_at_most_one_pinned_post()
    {
        Post::factory()->pinned()->create();
        Post::factory()->pinned()->create();

        Livewire::withoutLazyLoading();
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

        Livewire::withoutLazyLoading();
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
        $older->created_at = now()->subSeconds(60);
        $older->save();

        Livewire::withoutLazyLoading();
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

        Livewire::withoutLazyLoading();
        $testable = Livewire::test(Posts::class);
        $this->assertSame(3, $testable->instance()->posts->count());

        $testable->call('loadMore');
        $this->assertSame(6, $testable->instance()->posts->count());
    }

    public function test_loadMore_increments_page_property()
    {
        Post::factory()->notPinned()->create();

        Livewire::withoutLazyLoading();
        Livewire::test(Posts::class)
            ->assertSet('page', 1)
            ->call('loadMore')
            ->assertSet('page', 2);
    }

    public function test_has_more_posts_is_true_and_the_load_more_button_renders_while_posts_remain(): void
    {
        Post::factory()->count(4)->notPinned()->create();

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Posts::class);

        $this->assertTrue($component->instance()->hasMorePosts);
        $this->assertStringContainsString('$wire.loadMore', $component->html());

        $component->call('toggleView', 'list');
        $this->assertStringContainsString('$wire.loadMore', $component->html());
    }

    public function test_has_more_posts_flips_false_and_the_button_disappears_once_every_post_fits_the_page(): void
    {
        Post::factory()->count(2)->notPinned()->create();
        $total = Post::notPinned()->count();

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Posts::class);
        while ($component->get('page') * 3 < $total) {
            $component->call('loadMore');
        }

        $this->assertFalse($component->instance()->hasMorePosts);
        $this->assertStringNotContainsString('$wire.loadMore', $component->html());

        $component->call('toggleView', 'list');
        $this->assertStringNotContainsString('$wire.loadMore', $component->html());
    }

    public function test_total_posts_and_non_pinned_count_are_cached_under_model_cache_version_keys(): void
    {
        Post::factory()->count(4)->notPinned()->create();

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Posts::class);
        $component->instance()->totalPosts;
        $component->instance()->hasMorePosts;

        $this->assertTrue(Cache::has(ModelCacheVersion::key(Post::class, 'total_count:')));
        $nonPinnedKey = ModelCacheVersion::key(Post::class, 'non_pinned_count:');
        $this->assertTrue(Cache::has($nonPinnedKey));

        Post::factory()->notPinned()->create();

        $this->assertNotSame($nonPinnedKey, ModelCacheVersion::key(Post::class, 'non_pinned_count:'),'a post write must bump the version and orphan the cached non-pinned count');
    }

    public function test_total_posts_returns_count_of_all_posts()
    {
        $before = Post::forViewer()->count();
        Post::factory()->count(4)->notPinned()->create();
        Post::factory()->pinned()->create();

        Livewire::withoutLazyLoading();
        Livewire::test(Posts::class)
            ->assertSet('totalPosts', $before + 5);
    }

    public function test_select_post_sets_selected_post_from_database()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('selectPost', $post->id)
            ->assertSet('selectedPost.id', $post->id);
    }

    public function test_select_post_dispatches_open_post_panel_event()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::withoutLazyLoading();
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

        Livewire::withoutLazyLoading();
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

        Livewire::withoutLazyLoading();
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

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('selectPost', 999999)
            ->assertSet('selectedPost', null)
            ->assertNotDispatched('open-post-panel');
    }

    private function deptViewer(string $code): User
    {
        Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code]);

        return $user;
    }

    public function test_hidden_and_other_department_posts_are_absent_from_grid_and_totals_per_department(): void
    {
        $viewer = $this->deptViewer('ZZ-PA');
        $other = $this->deptViewer('ZZ-PB');
        $public = Post::factory()->notPinned()->create(['departments' => null]);
        $own = Post::factory()->notPinned()->create(['departments' => ['ZZ-PA']]);
        $foreign = Post::factory()->notPinned()->create(['departments' => ['ZZ-PB']]);
        $inactive = Post::factory()->notPinned()->create(['departments' => null, 'active' => false]);

        Livewire::withoutLazyLoading();
        $viewerPage = Livewire::actingAs($viewer)->test(Posts::class)->instance();
        $ids = $viewerPage->posts->pluck('id')->all();
        $viewerTotal = $viewerPage->totalPosts;

        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertSame(Post::forViewer($viewer)->count(), $viewerTotal);

        Livewire::withoutLazyLoading();
        $otherPage = Livewire::actingAs($other)->test(Posts::class)->instance();
        $this->assertSame(Post::forViewer($other)->count(), $otherPage->totalPosts, 'the cached total must be keyed per department');
        $this->assertNotContains($own->id, $otherPage->posts->pluck('id')->all());
        $this->assertContains($public->id, $otherPage->posts->pluck('id')->all());
    }

    public function test_select_post_on_inactive_or_other_department_post_returns_null_and_does_not_mark_read(): void
    {
        $viewer = $this->deptViewer('ZZ-PA');
        $inactive = Post::factory()->notPinned()->create(['departments' => null, 'active' => false]);
        $foreign = Post::factory()->notPinned()->create(['departments' => ['ZZ-PB']]);
        $this->insertPostNudge($viewer, $inactive->id);
        $this->insertPostNudge($viewer, $foreign->id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($viewer)->test(Posts::class)
            ->call('selectPost', $inactive->id)
            ->assertSet('selectedPost', null)
            ->call('selectPost', $foreign->id)
            ->assertSet('selectedPost', null)
            ->assertNotDispatched('open-post-panel');

        foreach ([$inactive, $foreign] as $post) {
            $this->assertNull(DB::table('notifications')->where('notifiable_id', $viewer->id)->where('data->item_id', $post->id)->value('read_at'));
        }
    }

    public function test_open_query_on_hidden_post_selects_nothing(): void
    {
        $viewer = $this->deptViewer('ZZ-PA');
        $hidden = Post::factory()->notPinned()->create(['departments' => null, 'active' => false]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($viewer)
            ->test(Posts::class, ['open' => $hidden->id])
            ->assertSet('selectedPost', null)
            ->assertNotDispatched('open-post-panel');
    }

    public function test_pins_respect_viewer_department_and_active_flag(): void
    {
        $viewer = $this->deptViewer('ZZ-PA');
        $foreignPin = Post::factory()->pinned()->create(['departments' => ['ZZ-PB'], 'created_at' => now()->addMinute()]);
        $inactivePin = Post::factory()->pinned()->create(['departments' => null, 'active' => false, 'created_at' => now()->addMinutes(2)]);

        Livewire::withoutLazyLoading();
        $pinIds = Livewire::actingAs($viewer)->test(Posts::class)->instance()->pins->pluck('id')->all();

        $this->assertNotContains($foreignPin->id, $pinIds);
        $this->assertNotContains($inactivePin->id, $pinIds);
    }

    public function test_search_narrows_pins_and_grid_by_title_or_body(): void
    {
        $viewer = $this->deptViewer('ZZ-PA');
        $pin = Post::factory()->pinned()->create(['title' => 'ALPHA-PIN-TITLE', 'departments' => null, 'created_at' => now()->addMinutes(5)]);
        $otherPin = Post::factory()->pinned()->create(['title' => 'BETA-PIN-TITLE', 'departments' => null, 'created_at' => now()->addMinute()]);
        $byTitle = Post::factory()->notPinned()->create(['title' => 'ALPHA-GRID-TITLE', 'departments' => null]);
        $byBody = Post::factory()->notPinned()->create(['title' => 'plain', 'body' => 'contains ALPHA-BODY-TOKEN here', 'departments' => null]);
        $miss = Post::factory()->notPinned()->create(['title' => 'gamma', 'body' => 'nothing', 'departments' => null]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($viewer)->test(Posts::class)->set('search', 'ALPHA');

        $this->assertSame([$pin->id], $component->instance()->pins->pluck('id')->all());
        $gridIds = $component->instance()->posts->pluck('id')->all();
        $this->assertContains($byTitle->id, $gridIds);
        $this->assertContains($byBody->id, $gridIds);
        $this->assertNotContains($miss->id, $gridIds);
        $this->assertNotContains($otherPin->id, $component->instance()->pins->pluck('id')->all());
        $this->assertSame(Post::forViewer($viewer)->where(fn($q) => $q->where('title', 'like', '%ALPHA%')->orWhere('body', 'like', '%ALPHA%'))->count(), $component->instance()->totalPosts);
    }

    public function test_search_never_bypasses_inactive_or_other_department_visibility(): void
    {
        $viewer = $this->deptViewer('ZZ-PA');
        $inactive = Post::factory()->notPinned()->create(['title' => 'SECRET-INACTIVE', 'departments' => null, 'active' => false]);
        $foreign = Post::factory()->notPinned()->create(['title' => 'SECRET-FOREIGN', 'departments' => ['ZZ-PB']]);
        $foreignPin = Post::factory()->pinned()->create(['title' => 'SECRET-PIN', 'departments' => ['ZZ-PB']]);
        $own = Post::factory()->notPinned()->create(['title' => 'SECRET-OWN', 'departments' => ['ZZ-PA']]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($viewer)->test(Posts::class)->set('search', 'SECRET');

        $ids = $component->instance()->posts->pluck('id')->all();
        $this->assertSame([$own->id], $ids);
        $this->assertTrue($component->instance()->pins->isEmpty());
        $this->assertNotContains($foreignPin->id, $component->instance()->pins->pluck('id')->all());
        $this->assertNotContains($inactive->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_search_with_no_match_renders_search_empty_state_in_both_views(): void
    {
        Post::factory()->notPinned()->create(['title' => 'present', 'departments' => null]);

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Posts::class)->set('search', 'ZZZ-NO-MATCH-XYZ');

        $component->assertSeeHtml('wire:key="posts-search-empty"')->assertDontSeeHtml('wire:key="posts-empty-grid"');
        $this->assertFalse($component->instance()->hasMorePosts);

        $component->call('toggleView', 'list')->assertSeeHtml('wire:key="posts-search-empty-list"');
    }

    public function test_search_escapes_like_wildcards_and_clearing_it_restores_the_full_list(): void
    {
        $percent = Post::factory()->notPinned()->create(['title' => 'sale 50% ZZWC', 'departments' => null]);
        $plain = Post::factory()->notPinned()->create(['title' => 'sale 500 ZZWC', 'departments' => null]);

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Posts::class)->set('search', '50%');
        $ids = $component->instance()->posts->pluck('id')->all();
        $this->assertContains($percent->id, $ids);
        $this->assertNotContains($plain->id, $ids);

        $component->set('search', '');
        $this->assertSame(Post::forViewer()->count(), $component->instance()->totalPosts);
        $this->assertContains($plain->id, Post::forViewer()->notPinned()->pluck('id')->all());
    }

    public function test_updated_search_resets_loaded_page_to_one(): void
    {
        Post::factory()->count(8)->notPinned()->create(['departments' => null]);

        Livewire::withoutLazyLoading();
        Livewire::test(Posts::class)
            ->call('loadMore')->call('loadMore')
            ->assertSet('page', 3)
            ->set('search', 'x')
            ->assertSet('page', 1);
    }

    public function test_cached_paths_stay_intact_and_unpoisoned_by_search(): void
    {
        Post::factory()->count(4)->notPinned()->create(['title' => 'CACHE-PATH', 'departments' => null]);
        $expectedTotal = Post::forViewer()->count();

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Posts::class);
        Cache::flush();
        $component->set('search', 'CACHE-PATH');
        $component->instance()->totalPosts;
        $component->instance()->hasMorePosts;
        $component->instance()->pins;

        $this->assertFalse(Cache::has(ModelCacheVersion::key(Post::class, 'total_count:')));
        $this->assertFalse(Cache::has(ModelCacheVersion::key(Post::class, 'non_pinned_count:')));

        $fresh = Livewire::test(Posts::class);
        $this->assertSame($expectedTotal, $fresh->instance()->totalPosts);
        $fresh->instance()->hasMorePosts;
        $this->assertTrue(Cache::has(ModelCacheVersion::key(Post::class, 'total_count:')));
        $this->assertTrue(Cache::has(ModelCacheVersion::key(Post::class, 'non_pinned_count:')));
    }

    public function test_seen_ids_returns_empty_collection_for_guest()
    {
        Livewire::withoutLazyLoading();
        $seenIds = Livewire::test(Posts::class)->instance()->seenIds;

        $this->assertTrue($seenIds->isEmpty());
    }

    public function test_seen_ids_reflects_read_notifications_for_authenticated_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();
        $this->insertPostNudge($user, $post->id, true);

        Livewire::withoutLazyLoading();
        $seenIds = Livewire::actingAs($user)->test(Posts::class)->instance()->seenIds;

        $this->assertTrue($seenIds->has($post->id));
    }

    public function test_focus_record_delegates_to_select_post()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Posts::class)
            ->call('focusRecord', $post->id)
            ->assertSet('selectedPost.id', $post->id);
    }

    public function test_mount_with_open_query_param_focuses_record()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Posts::class, ['open' => $post->id])
            ->assertSet('selectedPost.id', $post->id)
            ->assertDispatched('record-focus');
    }

    public function test_is_focusing_reflects_open_query_param_state()
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = Post::factory()->notPinned()->create();

        Livewire::withoutLazyLoading();
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

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Posts::class)->assertSet('view', 'card')->call('toggleView', 'list')->assertSet('view', 'list');
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Posts::class)->assertSet('view', 'list');
    }

    public function test_toggle_view_ignores_unsupported_value(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Posts::class)
            ->call('toggleView', 'list')->assertSet('view', 'list')
            ->call('toggleView', 'grid')->assertSet('view', 'list');
    }
}
