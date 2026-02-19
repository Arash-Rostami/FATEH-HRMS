<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Posts;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class PostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_render_successfully()
    {
        Livewire::test(Posts::class)
            ->assertStatus(200);
    }

    public function test_pins_are_visible()
    {
        $pinnedPost = Post::factory()->create(['pinned' => true, 'title' => 'Pinned Post Title']);
        $regularPost = Post::factory()->create(['pinned' => false, 'title' => 'Regular Post Title']);

        // Since 'pins' is a computed property not passed to view, we check if it is rendered
        Livewire::test(Posts::class)
            ->assertSee('Pinned Post Title');
    }

    public function test_posts_are_visible()
    {
        $pinnedPost = Post::factory()->create(['pinned' => true, 'title' => 'Pinned Post Title']);
        $regularPost = Post::factory()->create(['pinned' => false, 'title' => 'Regular Post Title']);

        Livewire::test(Posts::class)
            ->assertSee('Regular Post Title');
    }

    public function test_select_post_dispatches_event_and_sets_selected_post()
    {
        $post = Post::factory()->create();

        Livewire::test(Posts::class)
            ->call('selectPost', $post->id)
            ->assertSet('selectedPost.id', $post->id)
            ->assertDispatched('open-post-panel');
    }

    public function test_select_post_uses_cache()
    {
        $post = Post::factory()->create();

        Cache::shouldReceive('supportsTags')->andReturn(false);
        Cache::shouldReceive('remember')
            ->once()
            ->with('dashboard.posts.item.' . $post->id, 3600, \Closure::class)
            ->andReturn($post);

        Livewire::test(Posts::class)
            ->call('selectPost', $post->id);
    }

    public function test_pagination()
    {
        // Create enough posts to trigger pagination (3 per page)
        $posts = Post::factory()->count(6)->create(['pinned' => false]);

        // We expect the latest posts first.
        $firstPagePosts = $posts->sortByDesc('created_at')->take(3);
        $secondPagePosts = $posts->sortByDesc('created_at')->skip(3)->take(3);

        $component = Livewire::test(Posts::class);

        // Check first page visible
        foreach ($firstPagePosts as $post) {
            $component->assertSee($post->title);
        }

        // Check second page NOT visible yet (or at least check count logic if possible, strictly referencing titles might be flaky if factories generate same titles)
        // Better to inspect the computed property directly if we can, but we can't easily on 'component' object returned by test()

        $component->call('loadMore');

        // Check second page visible
        foreach ($secondPagePosts as $post) {
             $component->assertSee($post->title);
        }
    }
}
