<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Comment;
use App\Models\Feed;
use App\Models\Reaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Feeds extends Component
{
    #[Locked]
    public array $feedIds = [];

    public ?int $selectedFeedId = null;

    public bool $assetsLoaded = false;

    public array $newComments = [];
    public array $replyComments = [];

    public ?int $editingCommentId = null;
    public string $editingContent = '';

    public int $perPage = 3;
    public bool $hasMorePages = true;

    public function addComment($feedId, $parentId = null)
    {
        $target = $parentId ? "replyComments.{$parentId}" : "newComments.{$feedId}";

        $this->validate([
            $target => 'required|string|max:1000',
        ]);

        Comment::create([
            'feed_id' => $feedId,
            'user_id' => Auth::id(),
            'content' => $parentId ? $this->replyComments[$parentId] : $this->newComments[$feedId],
            'parent_id' => $parentId
        ]);

        $this->reset([$target]);
        unset($this->feeds);
    }

    #[On("delete-comment-confirmed")]
    public function deleteComment($commentId)
    {
        if (Comment::where('user_id', Auth::id())->where('id', $commentId)->delete()) {
            unset($this->feeds);
        }
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

    public function loadInitialFeeds()
    {
        $this->feedIds = Feed::latest()->take($this->perPage)->pluck('id')->toArray();
        $this->hasMorePages = count($this->feedIds) >= $this->perPage;

        if (!empty($this->feedIds) && !$this->selectedFeedId) {
            $this->selectedFeedId = $this->feedIds[0];
        }

        unset($this->feeds);
    }

    public function loadMore()
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

    public function mount()
    {
        $this->loadInitialFeeds();
        $this->assetsLoaded = true;
    }

    #[Computed]
    public function totalFeeds()
    {
        return Feed::count();
    }

    public function render()
    {
        return view('livewire.dashboard.tab.feeds.index');
    }

    public function startEditing($commentId)
    {
        $comment = Comment::where('user_id', Auth::id())->find($commentId);

        if ($comment) {
            $this->editingCommentId = $commentId;
            $this->editingContent = $comment->content;
        }
    }

    public function toggleReaction($feedId, $emoji)
    {
        if (!Auth::check()) return;

        DB::transaction(function () use ($feedId, $emoji) {
            $reaction = Reaction::where('feed_id', $feedId)
                ->where('user_id', Auth::id())
                ->first();

            if ($reaction) {
                $reaction->emoji === $emoji ? $reaction->delete() : $reaction->update(['emoji' => $emoji]);
            } else {
                Reaction::create([
                    'feed_id' => $feedId,
                    'user_id' => Auth::id(),
                    'emoji' => $emoji
                ]);
            }
        });

        unset($this->feeds);
    }

    public function updateComment()
    {
        if (!$this->editingCommentId) return;

        $this->validate([
            'editingContent' => 'required|string|max:1000',
        ]);

        $updated = Comment::where('user_id', Auth::id())
            ->where('id', $this->editingCommentId)
            ->update(['content' => $this->editingContent]);

        if ($updated) {
            unset($this->feeds);
        }

        $this->reset(['editingCommentId', 'editingContent']);
    }
}
