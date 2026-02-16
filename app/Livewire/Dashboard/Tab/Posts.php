<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Post;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Posts extends Component
{
    #[Locked]
    public int $page = 1;

    public $selectedPost = null;

    #[Computed]
    public function pins()
    {
        return Post::where('pinned', 1)->latest()->take(1)->get();
    }

    #[Computed]
    public function posts()
    {
        // Calculate offset based on current page
        $offset = ($this->page - 1) * 2;

        return Post::where('pinned', '<>', 1)
            ->orderByDesc('created_at')
            ->skip($offset)
            ->take(2)
            ->get();
    }

    public function loadMore()
    {
        $this->page++;
    }

    public function selectPost($postId)
    {
        $this->selectedPost = Post::find($postId);
        $this->dispatch('open-post-panel');
    }

    public function placeholder()
    {
        return view('livewire.dashboard.tab.placeholder');
    }

    public function render()
    {
        return view('livewire.dashboard.tab.posts');
    }
}
