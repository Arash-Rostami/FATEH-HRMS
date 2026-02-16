<?php

namespace App\Livewire\Dashboard\Tab;

use App\Services\CachedDashBoardData;
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
        return CachedDashBoardData::getPins();
    }

    #[Computed]
    public function posts()
    {
        // We use the service which calls simplePaginate(2, ..., page)
        // This ensures we respect the existing caching logic
        return CachedDashBoardData::getPosts($this->page);
    }

    public function loadMore()
    {
        $this->page++;
    }

    public function selectPost($postId)
    {
        // We don't have a service for single post, so we fetch directly or add to service.
        // For now, direct fetch is fine for detail view.
        $this->selectedPost = \App\Models\Post::find($postId);
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
