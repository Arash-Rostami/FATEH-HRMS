<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Comment;
use App\Models\Feed;
use App\Models\Reaction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class Feeds extends Component
{
    #[Locked]
    public array $feedIds = [];

    #[Url]
    public ?int $selectedFeedId = null;

    public array $newComments = [];

    public ?int $editingCommentId = null;
    public string $editingContent = '';

    public int $perPage = 10;
    public bool $hasMorePages = true;

    public function addComment($feedId)
    {
        $this->validate([
            'newComments.' . $feedId => 'required|string|max:1000',
        ]);

        $content = $this->newComments[$feedId];

        Comment::create([
            'feed_id' => $feedId,
            'user_id' => auth()->id(),
            'content' => $content,
        ]);

        $this->newComments[$feedId] = '';

        // Dispatch event to refresh UI if needed or show toast
        $this->dispatch('comment-added', feedId: $feedId);
    }

    public function deleteComment($commentId)
    {
        $comment = Comment::find($commentId);

        if ($comment && $comment->user_id === auth()->id()) {
            $comment->delete();
        }
    }

    #[Computed]
    public function feeds()
    {
        if (empty($this->feedIds)) return collect();

        return Feed::with(['user', 'comments.user', 'reactions', 'reactions.user'])
            ->whereIn('id', $this->feedIds)
            ->orderByRaw('FIELD(id, ' . implode(',', $this->feedIds) . ')')
            ->get();
    }

    public function loadInitialFeeds()
    {
        $query = Feed::latest();

        $feeds = $query->take($this->perPage)->get();

        $this->feedIds = $feeds->pluck('id')->toArray();
        $this->hasMorePages = $feeds->count() >= $this->perPage;

        if ($feeds->isNotEmpty() && !$this->selectedFeedId) {
            $this->selectedFeedId = $feeds->first()->id;
        }
    }

    public function loadMore()
    {
        if (!$this->hasMorePages) {
            return;
        }

        $existingCount = count($this->feedIds);

        $newFeeds = Feed::latest()
            ->skip($existingCount)
            ->take($this->perPage)
            ->pluck('id')
            ->toArray();

        if (empty($newFeeds)) {
            $this->hasMorePages = false;
            return;
        }

        $this->feedIds = array_merge($this->feedIds, $newFeeds);
    }

    public function mount()
    {
        $this->loadInitialFeeds();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.feeds.index');
    }

    public function startEditing($commentId)
    {
        $comment = Comment::find($commentId);

        if (!$comment || $comment->user_id !== auth()->id()) {
            return;
        }

        $this->editingCommentId = $commentId;
        $this->editingContent = $comment->content;
    }

    public function toggleReaction($feedId, $emoji)
    {
        if (!auth()->check()) {
            return;
        }

        DB::transaction(function () use ($feedId, $emoji) {
            $attrs = [
                'feed_id' => $feedId,
                'user_id' => auth()->id()
            ];

            $existingReaction = Reaction::where($attrs)->first();

            if ($existingReaction) {
                if ($existingReaction->emoji === $emoji) {
                    $existingReaction->delete();
                } else {
                    $existingReaction->update(['emoji' => $emoji]);
                }
            } else {
                Reaction::create(array_merge($attrs, ['emoji' => $emoji]));
            }
        });
    }

    public function updateComment()
    {
        if (!$this->editingCommentId) return;

        $this->validate([
            'editingContent' => 'required|string|max:1000',
        ]);

        $comment = Comment::find($this->editingCommentId);

        if ($comment && $comment->user_id === auth()->id()) {
            $comment->update([
                'content' => $this->editingContent,
            ]);
        }

        $this->editingCommentId = null;
        $this->editingContent = '';
    }
}
