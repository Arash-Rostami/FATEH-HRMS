<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Posts extends Component
{
    #[Locked]
    public int $page = 1;

    public $selectedPost = null;

    protected $listeners = ['select-post' => 'selectPost'];

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed(seconds: 3600, cache: true, key: 'dashboard.posts.pins')]
    public function pins()
    {
        return Post::where('pinned', 1)
            ->latest()
            ->take(1)
            ->get();
    }

    #[Computed]
    public function posts()
    {
        return Cache::remember('dashboard.posts.feeds.page.' . $this->page, 3600, function () {
            return Post::query()
                ->where('pinned', '<>', 1)
                ->orderByDesc('created_at')
                ->skip(($this->page - 1) * 3)
                ->take(3)
                ->get();
        });
    }

    public function render()
    {
        return view('livewire.dashboard.tab.posts');
    }

    public function selectPost($id)
    {
        $this->selectedPost = Cache::remember('dashboard.posts.item.' . $id, 3600, function () use ($id) {
            return Post::find($id);
        });
        $this->dispatch('open-post-panel');
    }
}
