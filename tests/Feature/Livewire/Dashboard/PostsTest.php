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

    public function test_pins_returns_only_pinned_posts()
    {
        $pinnedPost = Post::factory()->pinned()->create();
        $regularPost = Post::factory()->create();

        Livewire::test(Posts::class)
            ->assertViewHas('pins', function ($viewPins) use ($pinnedPost, $regularPost) {
                return $viewPins->contains($pinnedPost)
                    && !$viewPins->contains($regularPost);
            });
    }

    public function test_posts_returns_only_non_pinned_posts()
    {
        $pinnedPost = Post::factory()->pinned()->create();
        $regularPost = Post::factory()->create();

        Livewire::test(Posts::class)
            ->assertViewHas('posts', function ($viewPosts) use ($pinnedPost, $regularPost) {
                return $viewPosts->contains($regularPost)
                    && !$viewPosts->contains($pinnedPost);
            });
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

        Cache::shouldReceive('remember')
            ->once()
            ->with('dashboard.posts.item.' . $post->id, 3600, \Closure::class)
            ->andReturn($post);

        Livewire::test(Posts::class)
            ->call('selectPost', $post->id);
    }

    public function test_pagination()
    {
        Post::factory()->count(10)->create(); // 3 per page

        $component = Livewire::test(Posts::class);

        $component->assertViewHas('posts', function ($viewPosts) {
            return $viewPosts->count() === 3;
        });

        $component->call('loadMore');

        $component->assertViewHas('posts', function ($viewPosts) {
            return $viewPosts->count() === 6;
        });
    }
}
