<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\MarkPostAsReadAction;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

use App\Traits\FocusOnRecord;

class Posts extends Component
{
    use FocusOnRecord;

    public function focusRecord(int $id): void
    {
        $this->selectPost($id);
    }

    #[Locked]
    public int $page = 1;

    public $selectedPost = null;

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed(seconds: 3600, cache: true, key: 'dashboard.posts.pins')]
    public function pins()
    {
        return Post::with('user')
            ->where('pinned', 1)
            ->latest()
            ->take(1)
            ->get();
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->with('user')
            ->where(function ($query) {
                $query->where('pinned', '<>', 1)
                    ->orWhereNull('pinned');
            })
            ->orderByDesc('created_at')
            ->take($this->page * 3)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.posts');
    }

    #[On('select-post')]
    public function selectPost($id)
    {
        $this->selectedPost = Cache::remember('dashboard.posts.item.' . $id, 3600, fn() => Post::with('user')->find($id));

        if ($this->selectedPost && auth()->id()) {
            app(MarkPostAsReadAction::class)->execute((int) $id, (int) auth()->id());
        }

        $this->dispatch('open-post-panel');
    }

    #[Computed]
    public function seenIds()
    {
        $user = auth()->user();

        return $user !== null ? Post::seenIdsFor($user->id) : collect();
    }

    #[Computed]
    public function totalPosts()
    {
        return Post::count();
    }
}
