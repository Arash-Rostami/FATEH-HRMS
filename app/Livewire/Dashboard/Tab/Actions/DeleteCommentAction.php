<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class DeleteCommentAction
{
    public function execute(int $commentId): void
    {
        $comment = Comment::where('user_id', Auth::id())->find($commentId);

        if (!$comment) {
            return;
        }

        $comment->replies()->update(['parent_id' => $comment->parent_id]);
        $comment->delete();
    }
}
