<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\MarkPostAsReadAction;
use App\Models\Post;
use App\Services\Cache\ModelCacheVersion;
use App\Traits\FocusOnRecord;
use Illuminate\Database\Eloquent\Builder;
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

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

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
        $post = Post::cachedItem($id);

        if (!$post?->isVisibleTo($this->department())) {
            $this->selectedPost = null;
            return;
        }

        $this->selectedPost = $post;

        $userId = auth()->id();

        if ($userId) {
            $action->execute($id, $userId);
        }

        $this->dispatch('open-post-panel');
    }

    #[Computed]
    public function pins()
    {
        if (!$this->searching()) {
            return Post::cachedPins($this->department());
        }

        return $this->matching(Post::with('user')->where('pinned', 1)->forViewer())->latest()->take(1)->get();
    }

    #[Computed]
    public function posts()
    {
        return $this->matching(Post::forViewer())
            ->with('user')
            ->notPinned()
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
        if ($this->searching()) {
            return $this->matching(Post::forViewer())->count();
        }

        return ModelCacheVersion::remember(Post::class, "total_count:{$this->department()}", now()->addMinutes(15), fn () => Post::forViewer()->count());
    }

    #[Computed]
    public function hasMorePosts(): bool
    {
        if ($this->searching()) {
            return $this->posts()->count() < $this->matching(Post::forViewer())->notPinned()->count();
        }

        return $this->posts()->count() < ModelCacheVersion::remember(
            Post::class,
            "non_pinned_count:{$this->department()}",
            now()->addMinutes(15),
            fn () => Post::forViewer()->notPinned()->count()
        );
    }

    private function searching(): bool
    {
        return trim($this->search) !== '';
    }

    private function matching(Builder $query): Builder
    {
        if (!$this->searching()) {
            return $query;
        }

        $term = '%' . addcslashes(trim($this->search), '\\%_') . '%';

        return $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term));
    }

    private function department(): ?string
    {
        return auth()->user()?->profile?->department_id;
    }
}
