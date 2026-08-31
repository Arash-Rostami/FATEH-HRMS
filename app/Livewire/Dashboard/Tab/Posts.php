<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\MarkPostAsReadAction;
use App\Models\Post;
use App\Traits\FocusOnRecord;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
#[Lazy]
class Posts extends Component
{
    use FocusOnRecord;

    #[Locked]
    public int $page = 1;

    #[Locked]
    public string $view = 'card';

    public $selectedPost = null;

    public function mount(): void
    {
        $view = session('posts_view_mode', 'card');
        $this->view = in_array($view, ['card', 'list'], true) ? $view : 'card';
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.tab.posts.placeholder');
    }

    public function render()
    {
        return view('livewire.dashboard.tab.posts');
    }

    public function toggleView(string $view): void
    {
        if (!in_array($view, ['card', 'list'], true)) {
            return;
        }

        $this->view = $view;
        session(['posts_view_mode' => $view]);
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    public function focusRecord(int $id): void
    {
        $this->selectPost($id, app(MarkPostAsReadAction::class));
    }

    #[On('select-post')]
    public function selectPost(int $id, MarkPostAsReadAction $action): void
    {
        $this->selectedPost = Post::cachedItem($id);

        $userId = auth()->id();

        if ($this->selectedPost && $userId) {
            $action->execute($id, $userId);
        }

        $this->dispatch('open-post-panel');
    }

    #[Computed]
    public function pins()
    {
        return Post::cachedPins();
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->with('user')
            ->where(fn ($query) => $query->where('pinned', '<>', 1)->orWhereNull('pinned'))
            ->orderByDesc('created_at')
            ->take($this->page * 3)
            ->get();
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
