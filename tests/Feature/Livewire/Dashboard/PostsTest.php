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
        $pinnedPost = Post::factory()->create(['is_pinned' => true]);
        $regularPost = Post::factory()->create(['is_pinned' => false]);

        Livewire::test(Posts::class)
            ->assertViewHas('pins', function ($viewPins) use ($pinnedPost, $regularPost) {
                return $viewPins->contains($pinnedPost) && !$viewPins->contains($regularPost);
            });
    }

    public function test_select_post_uses_cache()
    {
        $post = Post::factory()->create();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($post);

        Livewire::test(Posts::class)
            ->call('selectPost', $post->id)
            ->assertSet('selectedPost.id', $post->id);
    }

    public function test_pagination()
    {
        Post::factory()->count(10)->create(['is_pinned' => false]);

        Livewire::test(Posts::class)
            ->assertViewHas('posts', fn($posts) => $posts->count() === 3)
            ->call('loadMore')
            ->assertViewHas('posts', fn($posts) => $posts->count() === 6);
    }
}
