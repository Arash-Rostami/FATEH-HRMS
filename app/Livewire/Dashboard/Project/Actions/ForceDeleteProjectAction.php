<?php

namespace App\Livewire\Dashboard\Project\Actions;

use App\Livewire\Dashboard\Channel\Actions\ForceDeleteChannelAction;
use App\Models\Channel;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDetail;
use Illuminate\Support\Facades\DB;

class ForceDeleteProjectAction
{
    public function execute(Project $project): bool|null
    {
        return DB::transaction(function () use ($project) {
            $taskIds = Task::withTrashed()->where('project_id', $project->id)->pluck('id');

            if ($taskIds->isNotEmpty()) {
                Task::withTrashed()->whereIn('id', $taskIds)->update(['project_id' => null]);
                TaskDetail::whereIn('task_id', $taskIds)->update(['project' => null]);
            }

            $channel = $project->channel_id ? Channel::find($project->channel_id) : null;
            if ($channel) {
                app(ForceDeleteChannelAction::class)->execute($channel);
            }

            $project->replies->each->delete();

            return $project->forceDelete();
        });
    }
}
