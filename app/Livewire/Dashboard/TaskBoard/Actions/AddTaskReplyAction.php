<?php

namespace App\Livewire\Dashboard\TaskBoard\Actions;

use App\Livewire\Dashboard\TaskBoard\Forms\ReplyForm;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;

class AddTaskReplyAction
{
    public function execute(ReplyForm $form, Task $task, User $user): Reply
    {
        abort_unless($user->id === $task->user_id || $user->id === $task->assigned_to, 403);

        $form->validate();

        return $task->addReply($user, $form->body, $form->files);
    }
}
