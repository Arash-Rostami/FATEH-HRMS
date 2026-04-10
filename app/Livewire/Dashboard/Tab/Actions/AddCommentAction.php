<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Livewire\Dashboard\Tab\Forms\CommentForm;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class AddCommentAction
{
    public function execute(CommentForm $form, int $feedId, ?int $parentId = null): void
    {
        $form->validate();

        Comment::create([
            'feed_id' => $feedId,
            'user_id' => Auth::id(),
            'content' => $form->content,
            'parent_id' => $parentId,
        ]);
    }
}
