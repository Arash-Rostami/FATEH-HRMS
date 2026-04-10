<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class DeleteCommentAction
{
    public function execute(int $commentId): void
    {
        Comment::where('user_id', Auth::id())
            ->where('id', $commentId)
            ->delete();
    }
}
