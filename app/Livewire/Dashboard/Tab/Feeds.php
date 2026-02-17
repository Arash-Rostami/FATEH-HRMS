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

    public bool $assetsLoaded = false;

    public array $newComments = [];
    public array $replyComments = [];

    public ?int $editingCommentId = null;
    public string $editingContent = '';

    public int $perPage = 3;
    public bool $hasMorePages = true;

    public function mount()
    {
        $this->loadInitialFeeds();
        $this->assetsLoaded = true;
    }

    public function loadInitialFeeds()
    {
        $feeds = Feed::latest()->take($this->perPage)->get();

        $this->feedIds = $feeds->pluck('id')->toArray();
        $this->hasMorePages = $feeds->count() >= $this->perPage;

        if ($feeds->isNotEmpty() && !$this->selectedFeedId) {
            $this->selectedFeedId = $feeds->first()->id;
        }

        unset($this->feeds);
    }

    public function loadMore()
    {
        if (!$this->hasMorePages) return;

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

        unset($this->feeds);

        $this->dispatch('feeds-extended');
    }

    #[Computed(persist: true, seconds: 7200)]
    public function feeds()
    {
        if (empty($this->feedIds)) return collect();

        return Feed::with([
            'user',
            'comments' => fn($q) => $q->whereNull('parent_id')->latest(),
            'comments.user',
            'comments.children.user',
            'reactions',
            'reactions.user'
        ])
            ->whereIn('id', $this->feedIds)
            ->orderByRaw('FIELD(id, ' . implode(',', $this->feedIds) . ')')
            ->get();
    }

    public function addComment($feedId, $parentId = null)
    {
        $target = $parentId ? 'replyComments.' . $parentId : 'newComments.' . $feedId;

        $this->validate([
            $target => 'required|string|max:1000',
        ]);

        $content = $parentId ? $this->replyComments[$parentId] : $this->newComments[$feedId];

        Comment::create([
            'feed_id' => $feedId,
            'user_id' => auth()->id(),
            'content' => $content,
            'parent_id' => $parentId
        ]);

        if ($parentId) {
            $this->replyComments[$parentId] = '';
        } else {
            $this->newComments[$feedId] = '';
        }

        unset($this->feeds);
    }

    public function deleteComment($commentId)
    {
        $comment = Comment::find($commentId);

        if ($comment && $comment->user_id === auth()->id()) {
            $comment->delete();
            unset($this->feeds);
        }
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
            unset($this->feeds);
        }

        $this->editingCommentId = null;
        $this->editingContent = '';
    }

    public function toggleReaction($feedId, $emoji)
    {
        if (!auth()->check()) return;

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

        unset($this->feeds);
    }

    public function render()
    {
        return view('livewire.dashboard.tab.feeds.index');
    }
}
