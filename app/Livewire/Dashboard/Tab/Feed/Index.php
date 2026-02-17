<?php

namespace App\Livewire\Dashboard\Tab\Feed;

use App\Models\Feed;
use App\Models\Reaction;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public $perPage = 10;
    public $loadedPages = 1;
    public $commentDrawerOpen = false;
    public $selectedFeedId = null;
    public $newComment = '';
    public $replyingToId = null;

    protected $listeners = ['feed-updated' => '$refresh'];

    #[Computed]
    public function feeds()
    {
        return Feed::with(['user', 'reactions', 'comments'])
            ->latest()
            ->take($this->perPage * $this->loadedPages)
            ->get();
    }

    #[Computed]
    public function selectedFeed()
    {
        if (!$this->selectedFeedId) return null;
        return Feed::with(['comments.user', 'comments.replies.user'])->find($this->selectedFeedId);
    }

    public function loadMore()
    {
        $this->loadedPages++; $this->dispatch('content-changed');
    }

    public function toggleReaction($feedId, $emoji)
    {
        $userId = auth()->id();

        DB::transaction(function () use ($feedId, $emoji, $userId) {
            $existing = Reaction::where('feed_id', $feedId)
                ->where('user_id', $userId)
                ->first();

            if ($existing && $existing->emoji === $emoji) {
                $existing->delete();
            } else {
                Reaction::updateOrCreate(
                    ['feed_id' => $feedId, 'user_id' => $userId],
                    ['emoji' => $emoji]
                );
            }
        });
    }

    public function openComments($feedId)
    {
        $this->selectedFeedId = $feedId;
        $this->commentDrawerOpen = true;
    }

    public function closeComments()
    {
        $this->commentDrawerOpen = false;
        $this->selectedFeedId = null;
        $this->newComment = '';
        $this->replyingToId = null;
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:1000',
        ]);

        if (!$this->selectedFeedId) return;

        Comment::create([
            'feed_id' => $this->selectedFeedId,
            'user_id' => auth()->id(),
            'content' => $this->newComment,
            'parent_id' => $this->replyingToId,
        ]);

        $this->newComment = '';
        $this->replyingToId = null;
    }

    public function setReply($commentId)
    {
        $this->replyingToId = $commentId;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.feed.index');
    }
}
