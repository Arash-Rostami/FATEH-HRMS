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

    protected $listeners = ['select-post' => 'selectPost'];

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed]
    public function pins()
    {
        return Post::where('pinned', 1)
            ->latest()
            ->take(1)
            ->get();
    }

    public function placeholder()
    {
        return view('livewire.dashboard.tab.placeholder');
    }

    #[Computed]
    public function posts()
    {
        $offset = ($this->page - 1) * 2;

        return Post::where('pinned', '<>', 1)
            ->orderByDesc('created_at')
            ->skip($offset)
            ->take(3)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.posts');
    }

    public function selectPost($id)
    {
        $this->selectedPost = Post::find($id);
        $this->dispatch('open-post-panel');
    }
}
