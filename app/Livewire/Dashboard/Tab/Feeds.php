<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Actions\AddCommentAction;
use App\Livewire\Dashboard\Tab\Actions\DeleteCommentAction;
use App\Livewire\Dashboard\Tab\Actions\ToggleReactionAction;
use App\Livewire\Dashboard\Tab\Actions\UpdateCommentAction;
use App\Livewire\Dashboard\Tab\Forms\CommentForm;
use App\Models\Comment;
use App\Models\Feed;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Traits\FocusOnRecord;

class Feeds extends Component
{
    use FocusOnRecord;
    #[Locked]
    public array $feedIds = [];

    public ?int $selectedFeedId = null;
    public bool $assetsLoaded = false;

    public array $newComments = [];
    public array $replyComments = [];

    public ?int $editingCommentId = null;
    public CommentForm $commentForm;

    public int $perPage = 3;
    public bool $hasMorePages = true;


    public function addComment($feedId, AddCommentAction $action, $parentId = null): void
    {
        $this->commentForm->content = $parentId
            ? $this->replyComments[$parentId]
            : $this->newComments[$feedId];

        $action->execute($this->commentForm, $feedId, $parentId);

        data_forget($this, $parentId ? "replyComments.$parentId" : "newComments.$feedId");
        $this->commentForm->reset('content');
        unset($this->feeds);
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
            'user',
            'comments' => fn($q) => $q->whereNull('parent_id')->latest(),
            'comments.user',
            'comments.children.user',
            'reactions',
            'reactions.user'
        ])
            ->whereIn('id', $this->feedIds)
            ->orderByRaw("FIELD(id, {$idsString})")
            ->get();
    }

    public function loadInitialFeeds(): void
    {
        $this->feedIds = Feed::latest()->take($this->perPage)->pluck('id')->toArray();
        $this->hasMorePages = count($this->feedIds) >= $this->perPage;

        if (!empty($this->feedIds) && !$this->selectedFeedId) {
            $this->selectedFeedId = $this->feedIds[0];
        }
        unset($this->feeds);
    }

    public function loadMore(): void
    {
        if (!$this->hasMorePages) return;

        $newIds = Feed::latest()
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

    public function mount(): void
    {
        if ($this->open && ! in_array($this->open, $this->feedIds ?? [], true)) {
            array_unshift($this->feedIds, $this->open);
            $this->selectedFeedId = $this->open;
        }

        $this->loadInitialFeeds();
        $this->assetsLoaded = true;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.feeds');
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
}
