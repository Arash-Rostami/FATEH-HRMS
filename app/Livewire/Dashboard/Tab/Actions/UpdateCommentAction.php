<?php

namespace App\Livewire\Dashboard\Tab\Actions;

use App\Livewire\Dashboard\Tab\Forms\CommentForm;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class UpdateCommentAction
{
    public function execute(CommentForm $form, int $commentId): void
    {
        $data = $form->validated();

        Comment::where('user_id', Auth::id())
            ->where('id', $commentId)
            ->update(['content' => $data['content']]);
    }
}
