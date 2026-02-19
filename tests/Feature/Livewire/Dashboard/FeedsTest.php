<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Feeds;
use App\Models\Comment;
use App\Models\Feed;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeedsTest extends TestCase
{
    use RefreshDatabase;

    public function test_feeds_render_successfully()
    {
        Livewire::test(Feeds::class)
            ->assertStatus(200);
    }

    public function test_loads_initial_feeds()
    {
        $feeds = Feed::factory()->count(5)->create();
        $expectedFeeds = $feeds->sortByDesc('created_at')->take(3);

        Livewire::test(Feeds::class)
            ->assertSet('hasMorePages', true)
            ->assertViewHas('feeds', function ($viewFeeds) use ($expectedFeeds) {
                return $viewFeeds->count() === 3
                    && $viewFeeds->pluck('id')->diff($expectedFeeds->pluck('id'))->isEmpty();
            });
    }

    public function test_load_more_feeds()
    {
        Feed::factory()->count(10)->create();

        $component = Livewire::test(Feeds::class);

        $component->call('loadMore');

        $component->assertViewHas('feeds', function ($viewFeeds) {
            return $viewFeeds->count() === 6; // 3 initial + 3 more
        });
    }

    public function test_user_can_add_comment()
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create();

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

    public function test_user_can_reply_to_comment()
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create();
        $parentComment = Comment::factory()->create(['feed_id' => $feed->id]);

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

    public function test_user_can_delete_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_others_comment()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $otherUser->id]);

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('deleteComment', $comment->id);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_user_can_edit_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id, 'content' => 'Old content']);

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('startEditing', $comment->id)
            ->assertSet('editingCommentId', $comment->id)
            ->assertSet('editingContent', 'Old content')
            ->set('editingContent', 'New content')
            ->call('updateComment');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'New content',
        ]);
    }

    public function test_user_cannot_edit_others_comment()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $otherUser->id, 'content' => 'Original content']);

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('startEditing', $comment->id)
            ->assertSet('editingCommentId', null); // Should not set editing state

        // Attempt direct update call anyway
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->set('editingCommentId', $comment->id)
            ->set('editingContent', 'Hacked content')
            ->call('updateComment');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Original content',
        ]);
    }

    public function test_user_can_toggle_reaction()
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create();

        // Add reaction
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        // Toggle (remove) reaction
        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '👍');

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '👍',
        ]);

        // Change reaction
        Reaction::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id, 'emoji' => '👍']);

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->call('toggleReaction', $feed->id, '❤️');

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'emoji' => '❤️',
        ]);
        $this->assertDatabaseMissing('reactions', ['emoji' => '👍']);
    }

    public function test_comment_validation()
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create();

        Livewire::actingAs($user)
            ->test(Feeds::class)
            ->set('newComments.' . $feed->id, '') // Empty
            ->call('addComment', $feed->id)
            ->assertHasErrors(['newComments.' . $feed->id => 'required'])
            ->set('newComments.' . $feed->id, str_repeat('a', 1001)) // Too long
            ->call('addComment', $feed->id)
            ->assertHasErrors(['newComments.' . $feed->id => 'max']);
    }
}
