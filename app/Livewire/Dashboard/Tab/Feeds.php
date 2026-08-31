<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\AddCommentAction;
use App\Livewire\Dashboard\Tab\Actions\DeleteCommentAction;
use App\Livewire\Dashboard\Tab\Actions\MarkFeedsAsReadAction;
use App\Livewire\Dashboard\Tab\Actions\ToggleReactionAction;
use App\Livewire\Dashboard\Tab\Actions\UpdateCommentAction;
use App\Livewire\Dashboard\Tab\Actions\VotePollAction;
use App\Livewire\Dashboard\Tab\Forms\CommentForm;
use App\Livewire\Dashboard\Tab\Presentation\FeedPresenter;
use App\Models\Comment;
use App\Models\Feed;
use App\Traits\FocusOnRecord;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
#[Lazy]
class Feeds extends Component
{
    use FocusOnRecord;

    #[Locked]
    public array $feedIds = [];

    #[Locked]
    public string $view = 'filmstrip';

    public ?int $selectedFeedId = null;
    public array $newComments = [];
    public array $replyComments = [];

    public ?int $editingCommentId = null;
    public CommentForm $commentForm;

    public array $openedCommentFeeds = [];

    public string $search = '';
    public ?string $selectedCategory = null;

    public int $perPage = 3;
    public bool $hasMorePages = true;

    public function addComment($feedId, AddCommentAction $action, $parentId = null): void
    {
        if (!Auth::check()) return;

        $this->commentForm->content = $parentId
            ? ($this->replyComments[$parentId] ?? '')
            : ($this->newComments[$feedId] ?? '');

        $action->execute($this->commentForm, $feedId, $parentId);

        data_forget($this, $parentId ? "replyComments.$parentId" : "newComments.$feedId");
        $this->commentForm->reset('content');
        unset($this->feeds);
    }

    #[Computed]
    public function categories()
    {
        return Feed::cachedCategories();
    }

    public function filterByCategory(?string $category): void
    {
        $this->open = null;
        $this->selectedCategory = $category === 'all' ? null : $category;
        $this->resetFeed();
    }

    public function updatedSearch(): void
    {
        $this->open = null;
        $this->resetFeed();
    }

    public function resetFilters(): void
    {
        $this->open = null;
        $this->reset(['search', 'selectedCategory']);
        $this->resetFeed();
    }

    #[On("delete-comment-confirmed")]
    public function deleteComment($commentId, DeleteCommentAction $action): void
    {
        $action->execute($commentId);
        unset($this->feeds);
    }

    #[Computed]
    public function feeds()
    {
        if (empty($this->feedIds)) return collect();

        $idsString = implode(',', $this->feedIds);
        return Feed::with([
            'user.profile',
            'comments' => fn($q) => $q->whereNull('parent_id')->latest(),
            'comments.user.profile',
            'comments.children.user.profile',
            'reactions',
            'reactions.user',
            'polls',
        ])
            ->withCount('comments')
            ->whereIn('id', $this->feedIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
    }

    public function loadInitialFeeds(): void
    {
        $query = $this->baseQuery()->latest();

        if ($this->hasActiveFilters()) {
            $this->feedIds = $query->pluck('id')->toArray();
            $this->hasMorePages = false;
        } else {
            $this->feedIds = $query->take($this->perPage)->pluck('id')->toArray();
            $this->hasMorePages = count($this->feedIds) >= $this->perPage;
        }

        $this->selectedFeedId = $this->feedIds[0] ?? null;

        unset($this->feeds);
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $newIds = $this->baseQuery()->latest()
            ->skip(count($this->feedIds))
            ->take($this->perPage)
            ->pluck('id')
            ->toArray();

        if (empty($newIds)) {
            $this->hasMorePages = false;
            return;
        }

        $this->feedIds = array_merge($this->feedIds, $newIds);
        unset($this->feeds);
    }

    public function focusRecord(int $id): bool
    {
        if (!Feed::whereKey($id)->exists()) {
            return false;
        }
        $this->view = 'filmstrip';
        $this->open = $id;
        $this->feedIds = [$id];
        $this->selectedFeedId = $id;
        $this->hasMorePages = false;
        $this->assetsLoaded = true;
        unset($this->feeds);
        return true;
    }

    public function toggleView(string $view): void
    {
        if (!in_array($view, ['filmstrip', 'magazine'], true)) {
            return;
        }
        $this->view = $view;
        session(['feeds_view_mode' => $view]);
    }

    public function mount(MarkFeedsAsReadAction $markFeedsAsReadAction): void
    {
        $view = session('feeds_view_mode', 'filmstrip');
        $this->view = in_array($view, ['filmstrip', 'magazine'], true) ? $view : 'filmstrip';

        if (Auth::id()) {
            $markFeedsAsReadAction->execute(Auth::id());
        }

        if ($this->open && Feed::whereKey($this->open)->exists()) {
            $this->view = 'filmstrip';
            $this->feedIds = [$this->open];
            $this->selectedFeedId = $this->open;
            $this->hasMorePages = false;
            $this->assetsLoaded = true;
            return;
        }

        $this->open = null;
        $this->loadInitialFeeds();
        $this->assetsLoaded = true;
    }

    public function restoreAfterFocus(): void
    {
        $this->open = null;
        $this->hasMorePages = true;
        $this->loadInitialFeeds();
    }

    public function openComments(int $feedId): void
    {
        $this->openedCommentFeeds[$feedId] = true;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.feeds', ['presenter' => new FeedPresenter()]);
    }

    public function placeholder(): \Illuminate\View\View
    {
        return view('livewire.dashboard.tab.feeds.placeholder');
    }

    public function startEditing($commentId): void
    {
        $comment = Comment::where('user_id', Auth::id())->find($commentId);
        if ($comment) {
            $this->editingCommentId = $commentId;
            $this->commentForm->content = $comment->content;
        }
    }

    public function toggleReaction($feedId, $emoji, ToggleReactionAction $action): void
    {
        if (!Auth::check()) return;

        $action->execute($feedId, $emoji);
        unset($this->feeds);
    }

    public function vote($feedId, $optionIndex, VotePollAction $action): void
    {
        if (!Auth::check()) return;

        $action->execute($feedId, (int) $optionIndex);
        unset($this->feeds);
    }

    #[Computed]
    public function totalFeeds(): int
    {
        return Feed::count();
    }

    public function updateComment(UpdateCommentAction $action): void
    {
        if (!$this->editingCommentId) return;

        $action->execute($this->commentForm, $this->editingCommentId);

        unset($this->feeds);
        $this->reset(['editingCommentId', 'commentForm']);
    }

    private function baseQuery()
    {
        return Feed::query()
            ->when($this->search !== '', fn($q) => $q->where('content', 'like', "%{$this->search}%"))
            ->when($this->selectedCategory, fn($q, $c) => $q->where('category', $c));
    }

    private function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->selectedCategory !== null;
    }

    private function resetFeed(): void
    {
        $this->selectedFeedId = null;
        $this->hasMorePages = true;
        $this->loadInitialFeeds();
    }
}
