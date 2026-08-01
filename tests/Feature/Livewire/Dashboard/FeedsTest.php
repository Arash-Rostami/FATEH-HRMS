<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Feeds;
use App\Models\Comment;
use App\Models\Feed;
use App\Models\Poll;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class FeedsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
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
        Livewire::test(Feeds::class)
            ->assertStatus(200);
    }

    public function test_loads_initial_feeds_limited_to_per_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $feeds = $this->createFeedsFor($user, 5);
        $expected = [$feeds[4]->id, $feeds[3]->id, $feeds[2]->id];

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

        Livewire::test(Feeds::class)
            ->assertSet('hasMorePages', true)
            ->call('loadMore')
            ->assertSet('feedIds', fn ($ids) => count($ids) === 6)
            ->assertSet('hasMorePages', true)
            ->call('loadMore')
            ->assertSet('hasMorePages', false)
            ->assertSet('feedIds', fn ($ids) => count($ids) === 6);
    }

    public function test_load_more_is_noop_when_has_more_pages_is_false(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 2);

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

        Livewire::test(Feeds::class)
            ->assertSet('totalFeeds', 4);
    }

    public function test_empty_state_renders_with_zero_feeds(): void
    {
        $this->cleanSlate();

        Livewire::test(Feeds::class)
            ->assertStatus(200)
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

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('startEditing', $comment->id)
            ->assertSet('editingCommentId', null);

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

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        Reaction::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'emoji' => '👍']);

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '❤️');

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '❤️',
        ]);
        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);
    }

    public function test_toggle_reaction_as_guest_is_noop(): void
    {
        $this->cleanSlate();
        $feed = Feed::factory()->create();

        Livewire::test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseMissing('reactions', ['feed_id' => $feed->id, 'emoji' => '👍']);
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

        Livewire::test(Feeds::class)
            ->call('filterByCategory', 'Poll')
            ->assertSet('selectedCategory', 'Poll')
            ->call('filterByCategory', 'all')
            ->assertSet('selectedCategory', null)
            ->assertSet('hasMorePages', true);
    }

    public function test_reset_filters_clears_search_and_category(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->cleanSlate();
        $this->createFeedsFor($user, 5);

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
            ->assertDispatched('record-focus', type: 'feeds', id: $target->id);
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
            ->assertSet('hasMorePages', true);
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
}