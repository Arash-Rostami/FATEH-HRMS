<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Actions\SuggestFeedReplyAction;
use App\Livewire\Dashboard\Tab\Feeds;
use App\Livewire\Dashboard\Tab\Presentation\FeedPresenter;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Feed;
use App\Models\Poll;
use App\Models\Profile;
use App\Models\Reaction;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class FeedsTest extends TestCase
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

    private function cleanSlate(): void
    {
        Comment::query()->delete();
        Reaction::query()->delete();
        Poll::query()->delete();
        Feed::query()->delete();
    }

    private function createFeedsFor(User $user, int $count, array $extra = []): array
    {
        $feeds = [];
        for ($i = 0; $i < $count; $i++) {
            $feeds[] = Feed::factory()->create(array_merge([
                'user_id' => $user->id,
                'content' => 'feed ' . ($i + 1),
                'created_at' => now()->subMinutes($count - $i),
            ], $extra));
        }
        return $feeds;
    }

    public function test_feeds_render_successfully(): void
    {
        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertStatus(200);
    }

    public function test_loads_initial_feeds_limited_to_per_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feeds = $this->createFeedsFor($user, 5);
        $expected = [$feeds[4]->id, $feeds[3]->id, $feeds[2]->id];

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSet('hasMorePages', true)
            ->assertSet('feedIds', fn ($ids) => $ids === $expected)
            ->assertSet('selectedFeedId', $feeds[4]->id);
    }

    public function test_load_more_appends_next_page_and_marks_no_more_when_exhausted(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 6);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSet('hasMorePages', true)
            ->call('loadMore')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 6)
            ->assertSet('hasMorePages', false)
            ->call('loadMore')
            ->assertSet('hasMorePages', false)
            ->assertSet('feedIds', fn ($ids) => count($ids) === 6);
    }

    public function test_feeds_created_in_the_same_second_page_in_a_stable_order_without_repeats(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 7, ['created_at' => now()->startOfSecond()]);

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Feeds::class)->call('loadMore')->call('loadMore');
        $ids = $component->get('feedIds');

        $this->assertCount(7, $ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertSame(Feed::orderByDesc('id')->pluck('id')->all(), $ids);
    }

    public function test_load_more_flips_the_flag_off_on_a_partial_final_batch(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 5);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSet('feedIds', fn ($ids) => count($ids) === 3)
            ->assertSet('hasMorePages', true)
            ->call('loadMore')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 5)
            ->assertSet('hasMorePages', false);
    }

    public function test_filter_branch_loads_every_match_without_pagination_and_flag_stays_false(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 5);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->set('search', 'feed')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 5)
            ->assertSet('hasMorePages', false);
    }

    public function test_total_feeds_is_cached_under_the_model_cache_version_key(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 3);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)->instance()->totalFeeds;

        $key = ModelCacheVersion::key(Feed::class, 'total_count:');
        $this->assertTrue(Cache::has($key));

        Feed::factory()->create(['user_id' => $user->id]);

        $this->assertNotSame($key, ModelCacheVersion::key(Feed::class, 'total_count:'),'a feed write must bump the version and orphan the cached total');
    }

    public function test_load_more_is_noop_when_has_more_pages_is_false(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 2);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSet('hasMorePages', false)
            ->assertSet('feedIds', fn ($ids) => count($ids) === 2)
            ->call('loadMore')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 2);
    }

    public function test_total_feeds_computed_counts_all_feeds(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 4);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSet('totalFeeds', 4);
    }

    public function test_empty_state_renders_with_zero_feeds(): void
    {
        $this->cleanSlate();

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertStatus(200)
            ->assertSeeHtml('feeds-empty-timeline')
            ->assertSet('feedIds', [])
            ->assertSet('hasMorePages', false)
            ->assertSet('selectedFeedId', null)
            ->assertSet('totalFeeds', 0);
    }

    public function test_user_can_add_comment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->set('newComments.' . $feed->id, 'This is a test comment')
            ->call('addComment', $feed->id)
            ->assertSet('newComments.' . $feed->id, '');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'content' => 'This is a test comment',
            'parent_id' => null,
        ]);
    }

    public function test_user_can_reply_to_comment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $parentComment = Comment::factory()->create(['feed_id' => $feed->id, 'user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->set('replyComments.' . $parentComment->id, 'This is a reply')
            ->call('addComment', $feed->id, $parentComment->id)
            ->assertSet('replyComments.' . $parentComment->id, '');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'content' => 'This is a reply',
            'parent_id' => $parentComment->id,
        ]);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $otherUser = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $otherUser->id]);
        $comment = Comment::factory()->create(['user_id' => $otherUser->id, 'feed_id' => $feed->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_user_can_edit_own_comment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'content' => 'Old content']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('startEditing', $comment->id)
            ->assertSet('editingCommentId', $comment->id)
            ->assertSet('commentForm.content', 'Old content')
            ->set('commentForm.content', 'New content')
            ->call('updateComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'New content',
        ]);
    }

    public function test_user_cannot_edit_others_comment(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $otherUser = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $otherUser->id]);
        $comment = Comment::factory()->create(['user_id' => $otherUser->id, 'feed_id' => $feed->id, 'content' => 'Original content']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('startEditing', $comment->id)
            ->assertSet('editingCommentId', null);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->set('editingCommentId', $comment->id)
            ->set('commentForm.content', 'Hacked content')
            ->call('updateComment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Original content',
        ]);
    }

    public function test_comment_validation_rejects_empty_and_overlong_content(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->set('newComments.' . $feed->id, '')
            ->call('addComment', $feed->id)
            ->assertHasErrors(['commentForm.content' => 'required'])
            ->set('newComments.' . $feed->id, str_repeat('a', 1001))
            ->call('addComment', $feed->id)
            ->assertHasErrors(['commentForm.content' => 'max']);
    }

    public function test_add_comment_for_untouched_input_shows_validation_error_not_crash(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('addComment', $feed->id)
            ->assertHasErrors(['commentForm.content' => 'required']);

        $this->assertDatabaseMissing('comments', ['feed_id' => $feed->id]);
    }

    public function test_add_reply_for_untouched_input_shows_validation_error_not_crash(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $parentComment = Comment::factory()->create(['feed_id' => $feed->id, 'user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('addComment', $feed->id, $parentComment->id)
            ->assertHasErrors(['commentForm.content' => 'required']);

        $this->assertDatabaseMissing('comments', ['parent_id' => $parentComment->id]);
    }

    public function test_add_comment_as_guest_is_noop(): void
    {
        $this->cleanSlate();
        $feed = Feed::factory()->create();

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->set('newComments.' . $feed->id, 'trying as guest')
            ->call('addComment', $feed->id);

        $this->assertDatabaseMissing('comments', ['feed_id' => $feed->id]);
    }

    public function test_user_can_toggle_reaction(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => bin2hex('👍'),
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => bin2hex('👍'),
        ]);

        Reaction::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'emoji' => '👍']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '❤️');

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => bin2hex('❤️'),
        ]);
        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => bin2hex('👍'),
        ]);
    }

    public function test_toggle_reaction_as_guest_is_noop(): void
    {
        $this->cleanSlate();
        $feed = Feed::factory()->create();

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseMissing('reactions', ['feed_id' => $feed->id, 'emoji' => bin2hex('👍')]);
    }

    public function test_vote_in_single_mode_replaces_previous_vote(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'category' => 'Poll',
            'poll_options' => ['single', '1', '1', 'Option A', 'Option B', 'Option C'],
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('vote', $feed->id, 0)
            ->call('vote', $feed->id, 1);

        $this->assertDatabaseHas('polls', [
            'user_id' => $user->id, 'feed_id' => $feed->id, 'option_index' => 1,
        ]);
        $this->assertDatabaseMissing('polls', [
            'user_id' => $user->id, 'feed_id' => $feed->id, 'option_index' => 0,
        ]);
        $this->assertSame(1, Poll::where('feed_id', $feed->id)->where('user_id', $user->id)->count());
    }

    public function test_vote_in_single_mode_with_same_option_removes_vote(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'category' => 'Poll',
            'poll_options' => ['single', '1', '1', 'Option A', 'Option B'],
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('vote', $feed->id, 0)
            ->call('vote', $feed->id, 0);

        $this->assertSame(0, Poll::where('feed_id', $feed->id)->where('user_id', $user->id)->count());
    }

    public function test_vote_in_multiple_mode_toggles_options_independently(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'category' => 'Poll',
            'poll_options' => ['multiple', '1', '1', 'Option A', 'Option B'],
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('vote', $feed->id, 0)
            ->call('vote', $feed->id, 0)
            ->call('vote', $feed->id, 1)
            ->call('vote', $feed->id, 0);

        $this->assertDatabaseHas('polls', [
            'user_id' => $user->id, 'feed_id' => $feed->id, 'option_index' => 0,
        ]);
        $this->assertDatabaseHas('polls', [
            'user_id' => $user->id, 'feed_id' => $feed->id, 'option_index' => 1,
        ]);
        $this->assertSame(2, Poll::where('feed_id', $feed->id)->where('user_id', $user->id)->count());
    }

    public function test_vote_with_out_of_range_option_index_is_ignored(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create([
            'user_id' => $user->id,
            'category' => 'Poll',
            'poll_options' => ['single', '1', '1', 'A', 'B', 'C'],
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('vote', $feed->id, 5)
            ->call('vote', $feed->id, -1);

        $this->assertSame(0, Poll::where('feed_id', $feed->id)->where('user_id', $user->id)->count());
    }

    public function test_vote_as_guest_is_noop(): void
    {
        $this->cleanSlate();
        $feed = Feed::factory()->create([
            'category' => 'Poll',
            'poll_options' => ['single', '1', '1', 'A', 'B'],
        ]);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->call('vote', $feed->id, 0);

        $this->assertSame(0, Poll::where('feed_id', $feed->id)->count());
    }

    public function test_search_filters_feeds_by_content(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        Feed::factory()->create(['user_id' => $user->id, 'content' => 'laravel is great']);
        Feed::factory()->create(['user_id' => $user->id, 'content' => 'laravel tips']);
        Feed::factory()->create(['user_id' => $user->id, 'content' => 'unrelated post']);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->set('search', 'laravel')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 2)
            ->assertSet('hasMorePages', false);
    }

    public function test_search_with_falsy_zero_string_filters_to_matching_feeds_only(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $matchA = Feed::factory()->create(['user_id' => $user->id, 'content' => 'has 0 in it']);
        $matchB = Feed::factory()->create(['user_id' => $user->id, 'content' => 'has 0 in it too']);
        $nomatch = Feed::factory()->create(['user_id' => $user->id, 'content' => 'no match here']);

        Livewire::withoutLazyLoading();
        $ids = Livewire::test(Feeds::class)
            ->set('search', '0')
            ->get('feedIds');

        $this->assertCount(2, $ids);
        $this->assertContains($matchA->id, $ids);
        $this->assertContains($matchB->id, $ids);
        $this->assertNotContains($nomatch->id, $ids);
    }

    public function test_updated_search_resets_feed_list_to_filtered_query(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        Feed::factory()->create(['user_id' => $user->id, 'content' => 'alpha one', 'created_at' => now()->subMinutes(1)]);
        Feed::factory()->create(['user_id' => $user->id, 'content' => 'alpha two', 'created_at' => now()]);
        Feed::factory()->create(['user_id' => $user->id, 'content' => 'beta three', 'created_at' => now()->subMinutes(3)]);
        Feed::factory()->create(['user_id' => $user->id, 'content' => 'beta four', 'created_at' => now()->subMinutes(2)]);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->call('loadMore')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 4)
            ->set('search', 'alpha')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 2)
            ->assertSet('hasMorePages', false);
    }

    public function test_filter_by_category_scopes_feeds_to_selected_category(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        Feed::factory()->create(['user_id' => $user->id, 'category' => 'Poll']);
        Feed::factory()->create(['user_id' => $user->id, 'category' => 'Poll']);
        Feed::factory()->create(['user_id' => $user->id, 'category' => 'Event']);
        Feed::factory()->create(['user_id' => $user->id, 'category' => 'Event']);
        Feed::factory()->create(['user_id' => $user->id, 'category' => 'Event']);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->call('filterByCategory', 'Poll')
            ->assertSet('selectedCategory', 'Poll')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 2)
            ->assertSet('hasMorePages', false);
    }

    public function test_filter_by_category_all_token_clears_selected_category(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 4);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->call('filterByCategory', 'Poll')
            ->assertSet('selectedCategory', 'Poll')
            ->call('filterByCategory', 'all')
            ->assertSet('selectedCategory', null)
            ->assertSet('hasMorePages', true);
    }

    public function test_month_filter_renders_when_feeds_have_created_at_dates(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 2);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSee('calendar_month', false)
            ->assertSee('همه ماه‌ها', false);
    }

    public function test_presenter_months_returns_unique_keys_sorted_desc_by_created_at(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $march = Feed::factory()->create(['user_id' => $user->id, 'created_at' => '2026-03-15 10:00:00']);
        $march2 = Feed::factory()->create(['user_id' => $user->id, 'created_at' => '2026-03-20 10:00:00']);
        $may = Feed::factory()->create(['user_id' => $user->id, 'created_at' => '2026-05-10 10:00:00']);

        $feeds = collect([$march, $march2, $may]);
        $months = (new FeedPresenter())->months($feeds, 'created_at');

        $this->assertCount(2, $months);
        $this->assertSame(2, $months->pluck('key')->unique()->count());
        $this->assertSame('2026-05-10', $months->first()['sort']->toDateString());
    }

    public function test_reset_filters_clears_search_and_category(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 5);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->set('search', 'feed')
            ->call('filterByCategory', 'Poll')
            ->assertSet('search', 'feed')
            ->assertSet('selectedCategory', 'Poll')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('selectedCategory', null);
    }

    public function test_open_comments_flips_opened_comment_feeds_flag_for_feed(): void
    {
        $this->cleanSlate();
        $feed = Feed::factory()->create();

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSet('openedCommentFeeds', [])
            ->call('openComments', $feed->id)
            ->assertSet('openedCommentFeeds', [$feed->id => true]);
    }

    public function test_mount_with_open_query_loads_only_that_feed_and_disables_pagination(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 5);
        $target = Feed::factory()->create(['user_id' => $user->id, 'content' => 'focused feed', 'created_at' => now()]);

        Livewire::withQueryParams(['open' => $target->id])
            ->actingAs($user)
            ->test(Feeds::class)
            ->assertSet('open', $target->id)
            ->assertSet('feedIds', [$target->id])
            ->assertSet('selectedFeedId', $target->id)
            ->assertSet('hasMorePages', false)
            ->assertNotDispatched('record-focus');
    }

    public function test_mount_with_open_query_for_nonexistent_feed_falls_back_to_initial_load(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 3);

        Livewire::withQueryParams(['open' => 999999])
            ->actingAs($user)
            ->test(Feeds::class)
            ->assertSet('open', null)
            ->assertSet('feedIds', fn ($ids) => count($ids) === 3)
            ->assertSet('hasMorePages', false);
    }

    private function deptViewer(string $code): User
    {
        Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => '']);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code]);

        return $user;
    }

    public function test_hidden_and_other_department_feeds_are_absent_and_totals_and_categories_follow_the_viewer(): void
    {
        $this->cleanSlate();
        $viewer = $this->deptViewer('ZZ-FA');
        $other = $this->deptViewer('ZZ-FB');
        $public = Feed::factory()->create(['user_id' => $viewer->id, 'category' => 'ZZ-Pub', 'departments' => null]);
        $own = Feed::factory()->create(['user_id' => $viewer->id, 'category' => 'ZZ-Own', 'departments' => ['ZZ-FA']]);
        $foreign = Feed::factory()->create(['user_id' => $viewer->id, 'category' => 'ZZ-Foreign', 'departments' => ['ZZ-FB']]);
        $inactive = Feed::factory()->create(['user_id' => $viewer->id, 'category' => 'ZZ-Off', 'departments' => null, 'active' => false]);

        Livewire::withoutLazyLoading();
        $page = Livewire::actingAs($viewer)->test(Feeds::class);
        $ids = $page->get('feedIds');

        $this->assertEqualsCanonicalizing([$public->id, $own->id], $ids);
        $this->assertSame(2, $page->instance()->totalFeeds);
        $this->assertEqualsCanonicalizing(['ZZ-Pub', 'ZZ-Own'], $page->instance()->categories->all());

        Livewire::withoutLazyLoading();
        $otherPage = Livewire::actingAs($other)->test(Feeds::class);
        $this->assertEqualsCanonicalizing([$public->id, $foreign->id], $otherPage->get('feedIds'));
        $this->assertSame(2, $otherPage->instance()->totalFeeds);
        $this->assertEqualsCanonicalizing(['ZZ-Pub', 'ZZ-Foreign'], $otherPage->instance()->categories->all());
    }

    public function test_open_query_on_a_hidden_feed_falls_back_and_focus_record_refuses_it(): void
    {
        $this->cleanSlate();
        $viewer = $this->deptViewer('ZZ-FA');
        $visible = Feed::factory()->create(['user_id' => $viewer->id, 'departments' => null]);
        $inactive = Feed::factory()->create(['user_id' => $viewer->id, 'departments' => null, 'active' => false]);
        $foreign = Feed::factory()->create(['user_id' => $viewer->id, 'departments' => ['ZZ-FB']]);

        foreach ([$inactive, $foreign] as $hidden) {
            Livewire::withoutLazyLoading();
            Livewire::withQueryParams(['open' => $hidden->id])
                ->actingAs($viewer)
                ->test(Feeds::class)
                ->assertSet('open', null)
                ->assertSet('feedIds', [$visible->id]);
        }

        Livewire::withoutLazyLoading();
        $page = Livewire::actingAs($viewer)->test(Feeds::class);
        $this->assertFalse($page->instance()->focusRecord($inactive->id));
        $this->assertFalse($page->instance()->focusRecord($foreign->id));
    }

    public function test_engagement_actions_on_feeds_that_are_not_loaded_are_noops(): void
    {
        $this->cleanSlate();
        $viewer = $this->deptViewer('ZZ-FA');
        $hidden = Feed::factory()->create(['user_id' => $viewer->id, 'departments' => ['ZZ-FB']]);
        $inactive = Feed::factory()->create(['user_id' => $viewer->id, 'active' => false]);
        $before = [Comment::count(), Reaction::count()];

        Livewire::withoutLazyLoading();
        $page = Livewire::actingAs($viewer)->test(Feeds::class);
        foreach ([$hidden, $inactive] as $feed) {
            $page->set('newComments.' . $feed->id, 'sneaky')
                ->call('addComment', $feed->id)
                ->call('toggleReaction', $feed->id, '👍')
                ->call('vote', $feed->id, 0);
        }

        $this->assertSame($before, [Comment::count(), Reaction::count()]);
        $this->assertSame(0, Reaction::whereIn('feed_id', [$hidden->id, $inactive->id])->count());
    }

    public function test_clear_focus_restores_initial_feed_load(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 5);
        $target = Feed::factory()->create(['user_id' => $user->id, 'content' => 'focused', 'created_at' => now()]);

        Livewire::withQueryParams(['open' => $target->id])
            ->actingAs($user)
            ->test(Feeds::class)
            ->assertSet('open', $target->id)
            ->assertSet('hasMorePages', false)
            ->call('clearFocus')
            ->assertSet('open', null)
            ->assertSet('hasMorePages', true)
            ->assertSet('feedIds', fn ($ids) => count($ids) === 3);
    }

    public function test_delete_comment_reparents_replies_to_grandparent_instead_of_orphaning(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);

        $root = Comment::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'parent_id' => null]);
        $middle = Comment::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'parent_id' => $root->id]);
        $leaf = Comment::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'parent_id' => $middle->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('deleteComment', $middle->id);

        $this->assertDatabaseMissing('comments', ['id' => $middle->id]);
        $this->assertDatabaseHas('comments', ['id' => $root->id, 'parent_id' => null]);
        $this->assertDatabaseHas('comments', ['id' => $leaf->id, 'parent_id' => $root->id]);
    }

    public function test_mount_marks_all_unread_feed_nudges_as_read_for_authenticated_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feedA = Feed::factory()->create(['user_id' => $user->id]);
        $feedB = Feed::factory()->create(['user_id' => $user->id]);
        $this->insertFeedNudge($user, $feedA->id);
        $this->insertFeedNudge($user, $feedB->id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->assertStatus(200);

        $this->assertSame(
            0,
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('data->menu_key', Feed::NUDGE_KEY)
                ->whereNull('read_at')
                ->count()
        );
    }

    public function test_mount_does_not_mark_feed_nudges_read_for_guest(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id]);
        $this->insertFeedNudge($user, $feed->id);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)->assertStatus(200);

        $this->assertNull(
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('data->menu_key', Feed::NUDGE_KEY)
                ->value('read_at')
        );
    }

    private function insertFeedNudge(User $user, int $feedId, bool $read = false): void
    {
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'database',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'menu_key' => Feed::NUDGE_KEY,
                'item_id' => $feedId,
                'title' => 'test nudge',
                'body' => 'test body',
            ]),
            'read_at' => $read ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_toggle_view_to_magazine_sets_view_and_persists_session(): void
    {
        Livewire::withoutLazyLoading();
        $component = Livewire::test(Feeds::class)->call('toggleView', 'magazine');

        $this->assertSame('magazine', $component->instance()->view);
        $this->assertSame('magazine', session('feeds_view_mode'));
    }

    public function test_toggle_view_ignores_invalid_value(): void
    {
        Livewire::withoutLazyLoading();
        $component = Livewire::test(Feeds::class)->call('toggleView', 'bogus');

        $this->assertSame('filmstrip', $component->instance()->view);
        $this->assertNull(session('feeds_view_mode'));
    }

    public function test_mount_restores_magazine_view_from_session(): void
    {
        session(['feeds_view_mode' => 'magazine']);

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Feeds::class);

        $this->assertSame('magazine', $component->instance()->view);
    }

    public function test_focus_record_loads_single_feed_and_forces_filmstrip(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        [$feed] = $this->createFeedsFor($user, 1);

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Feeds::class);
        $ok = $component->instance()->focusRecord($feed->id);

        $this->assertTrue($ok);
        $this->assertSame('filmstrip', $component->instance()->view);
        $this->assertSame([$feed->id], $component->instance()->feedIds);
        $this->assertSame($feed->id, $component->instance()->selectedFeedId);
        $this->assertFalse($component->instance()->hasMorePages);
    }

    public function test_focus_record_ignores_nonexistent_id(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 1);

        Livewire::withoutLazyLoading();
        $component = Livewire::test(Feeds::class);
        $viewBefore = $component->instance()->view;
        $feedIdsBefore = $component->instance()->feedIds;
        $openBefore = $component->instance()->open;

        $ok = $component->instance()->focusRecord(99999999);

        $this->assertFalse($ok);
        $this->assertSame($viewBefore, $component->instance()->view);
        $this->assertSame($feedIdsBefore, $component->instance()->feedIds);
        $this->assertSame($openBefore, $component->instance()->open);
    }

    public function test_media_cell_data_flags_images_and_videos()
    {
        $p = new FeedPresenter();

        $image = $p->mediaCellData('http://x/y.png');
        $this->assertTrue($image['isImage']);
        $this->assertStringContainsString('cursor-zoom-in', $image['cellClass']);
        $this->assertArrayNotHasKey('myIndex', $image);

        $video = $p->mediaCellData('http://x/y.mp4');
        $this->assertFalse($video['isImage']);
        $this->assertStringNotContainsString('cursor-zoom-in', $video['cellClass']);
    }

    public function test_composer_entangle_keys_are_seeded_for_every_loaded_feed(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feeds = $this->createFeedsFor($user, 2);

        Livewire::withoutLazyLoading();
        Livewire::test(Feeds::class)
            ->assertSet('newComments', array_fill_keys(array_map(fn ($feed) => $feed->id, $feeds), ''));
    }

    private function feedAiUserWithFeed(string $content = 'جلسهٔ معرفی محصول فردا برگزار می‌شود'): array
    {
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'ai.gateway.model' => 'Gemini-3-Flash-Preview', 'ai.feed.cooldown' => 0, 'app.off' => []]);

        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feed = Feed::factory()->create(['user_id' => $user->id, 'content' => $content]);

        $forget = function () use ($user) {
            Cache::forget(Feed::quotaKey($user->id));
            Cache::forget("feed:ai:cooldown:{$user->id}");
        };
        $forget();
        $this->beforeApplicationDestroyed($forget);

        return [$user, $feed];
    }

    private function feedAiReply(string $reply): array
    {
        return [
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE)]]],
            'usage' => ['total_tokens' => 10],
        ];
    }

    public function test_suggest_reply_drafts_a_comment_into_the_composer_and_charges_one_quota_unit(): void
    {
        [$user, $feed] = $this->feedAiUserWithFeed();
        Http::fake(['*' => Http::response($this->feedAiReply('حتماً شرکت می‌کنم'))]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Feeds::class)
            ->call('suggestReply', $feed->id)
            ->assertSet('newComments.' . $feed->id, 'حتماً شرکت می‌کنم');

        $this->assertSame(1, Feed::quotaUsed($user->id));
    }

    public function test_suggest_reply_inside_the_cooldown_is_busy_without_calling_the_gateway_and_works_after_it_clears(): void
    {
        [$user, $feed] = $this->feedAiUserWithFeed();
        config(['ai.feed.cooldown' => 3]);
        $this->actingAs($user);
        Http::fake(['*' => Http::response($this->feedAiReply('x'))]);
        $action = app(SuggestFeedReplyAction::class);

        $this->assertNull($action->execute($feed->id)['error']);

        $result = $action->execute($feed->id);
        $this->assertSame('busy', $result['error']);
        $this->assertSame(config('ai.feed.errors.busy'), $result['message']);
        $this->assertSame(1, Feed::quotaUsed($user->id));
        Http::assertSentCount(1);

        Cache::forget("feed:ai:cooldown:{$user->id}");
        $this->assertNull($action->execute($feed->id)['error']);
        Http::assertSentCount(2);
    }

    public function test_suggest_reply_answers_a_parent_comment_into_the_reply_box(): void
    {
        [$user, $feed] = $this->feedAiUserWithFeed();
        $colleague = User::factory()->create(['status' => 'active']);
        $parent = Comment::factory()->create(['feed_id' => $feed->id, 'user_id' => $colleague->id, 'content' => 'نظر همکار']);
        Http::fake(['*' => Http::response($this->feedAiReply('پاسخ پیشنهادی'))]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Feeds::class)
            ->call('suggestReply', $feed->id, $parent->id)
            ->assertSet('replyComments.' . $parent->id, 'پاسخ پیشنهادی');
    }

    public function test_suggest_reply_never_overwrites_a_draft_and_charges_no_quota(): void
    {
        [$user, $feed] = $this->feedAiUserWithFeed();
        $colleague = User::factory()->create(['status' => 'active']);
        $parent = Comment::factory()->create(['feed_id' => $feed->id, 'user_id' => $colleague->id, 'content' => 'نظر همکار']);
        Http::fake(['*' => Http::response($this->feedAiReply('x'))]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Feeds::class)
            ->set('newComments.' . $feed->id, 'پیش‌نویس من')
            ->call('suggestReply', $feed->id)
            ->assertSet('newComments.' . $feed->id, 'پیش‌نویس من')
            ->assertDispatched('toast', message: config('ai.feed.errors.draft'), type: 'warning')
            ->set('replyComments.' . $parent->id, 'پاسخ ناتمام')
            ->call('suggestReply', $feed->id, $parent->id)
            ->assertSet('replyComments.' . $parent->id, 'پاسخ ناتمام');

        $this->assertSame(0, Feed::quotaUsed($user->id));
        Http::assertNothingSent();
    }

    public function test_comment_meta_carries_a_relative_time_and_the_full_jalali_date_for_the_tooltip(): void
    {
        $comment = Comment::factory()->create(['created_at' => now()->subHours(2)]);

        $meta = (new FeedPresenter())->commentMeta($comment, null);

        $this->assertSame(convertToPersian('2 ساعت پیش'), $meta['ago']);
        $this->assertSame(convertToPersian(toJalali($comment->created_at, 'j F Y')), $meta['date']);
    }

    public function test_comment_thread_ux_hooks_stay_wired_in_the_comments_partial(): void
    {
        $view = file_get_contents(resource_path('views/livewire/dashboard/tab/feeds/comments.blade.php'));

        $this->assertStringContainsString('x-data="feedComposer(', $view);
        $this->assertStringContainsString('lg:opacity-0 lg:group-hover:opacity-100', $view);
        $this->assertStringNotContainsString('showReplies', $view);
        $this->assertStringContainsString('data-reply-input=', $view);
        $this->assertStringContainsString('@keydown.escape.stop="closeReply()"', $view);
        $this->assertStringContainsString('wire:keydown.escape.stop="$set(\'editingCommentId\', null)"', $view);
        $this->assertStringContainsString('@keydown.enter="onReplyEnter($event,', $view);
        $this->assertStringNotContainsString('.exact', $view);
        $this->assertStringContainsString('wire:keydown.ctrl.enter="updateComment"', $view);
        $this->assertStringContainsString('x-on:click="openReply(', $view);
    }

    public function test_suggest_reply_refuses_a_guest_or_a_tenant_that_switched_it_off_without_charging_quota(): void
    {
        [$user, $feed] = $this->feedAiUserWithFeed();
        Http::fake(['*' => Http::response($this->feedAiReply('x'))]);
        $action = app(SuggestFeedReplyAction::class);

        $this->assertSame('unreachable', $action->execute($feed->id)['error']);

        $this->actingAs($user);
        config(['app.off' => ['feed:ai']]);
        $this->assertSame('unreachable', $action->execute($feed->id)['error']);
        $this->assertSame(0, Feed::quotaUsed($user->id));
        Http::assertNothingSent();
    }

    public function test_suggest_reply_stops_at_the_daily_cap_or_an_empty_feed_without_calling_the_gateway(): void
    {
        [$user, $feed] = $this->feedAiUserWithFeed();
        $this->actingAs($user);
        Http::fake(['*' => Http::response($this->feedAiReply('x'))]);
        $action = app(SuggestFeedReplyAction::class);

        for ($i = 0; $i < Feed::quotaCap(); $i++) {
            Feed::consumeQuota($user->id);
        }

        $result = $action->execute($feed->id);
        $this->assertSame('quota', $result['error']);
        $this->assertSame(Feed::quotaCap(), Feed::quotaUsed($user->id));

        Cache::forget(Feed::quotaKey($user->id));
        $bare = Feed::factory()->create(['user_id' => $user->id, 'content' => null]);
        $result = $action->execute($bare->id);
        $this->assertSame('none', $result['error']);
        $this->assertSame(0, Feed::quotaUsed($user->id));

        Http::assertNothingSent();
    }

    public function test_suggest_reply_refunds_the_daily_quota_when_the_gateway_is_unreachable(): void
    {
        [$user, $feed] = $this->feedAiUserWithFeed();
        $this->actingAs($user);
        Http::fake(['*' => Http::failedConnection()]);

        $result = app(SuggestFeedReplyAction::class)->execute($feed->id);

        $this->assertSame('unreachable', $result['error']);
        $this->assertSame(config('ai.feed.errors.unreachable'), $result['message']);
        $this->assertSame(0, Feed::quotaUsed($user->id));

        Http::assertSentCount(1);
        app(SuggestFeedReplyAction::class)->execute($feed->id);
        Http::assertSentCount(1);
        Cache::forget('feed:ai:down');
    }

    public function test_feed_ai_buttons_sit_behind_the_gate_helper_in_the_comments_partial(): void
    {
        $view = file_get_contents(resource_path('views/livewire/dashboard/tab/feeds/comments.blade.php'));

        $this->assertMatchesRegularExpression('/@if\(feedAiOn\(\)\)\s*<x-ui\.buttons\.form wire:key="feed-ai-suggest" variant="ai" size="sm".{0,400}wire:click="suggestReply\(/s', $view);
        $this->assertMatchesRegularExpression('/@if\(feedAiOn\(\) && !\$meta\[\'isOwner\'\]\)\s*<x-ui\.buttons\.form wire:key="feed-ai-reply" variant="ai" size="sm".{0,400}wire:click="suggestReply\(/s', $view);
        $this->assertSame(2, substr_count($view, 'loading="suggestReply('));
        $this->assertSame(2, substr_count($view, '<x-ui.loaders.ai-shimmer'));
    }
}
