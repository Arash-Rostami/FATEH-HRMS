<?php

namespace App\Jobs;

use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTask\ChannelProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SyncProjectChannelMembershipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(public int $projectId) { }

    public function handle(ChannelProvisioner $provisioner): void
    {
        $project = Project::find($this->projectId);

        if (!$project) return;

        $channel = $provisioner->resolve($project);

        $targetIds = $this->resolveAudienceUserIds($project);
        $currentIds = $channel->memberUsers()->pluck('users.id');

        $toAdd = $targetIds->diff($currentIds);
        $toRemove = $currentIds->diff($targetIds);

        if ($toAdd->isNotEmpty()) {
            $channel->memberUsers()->syncWithoutDetaching(
                $toAdd->mapWithKeys(fn($id) => [$id => [
                    'joined_at' => now(),
                    'entered_at' => null,
                    'last_read_message_id' => null,
                ]])->all()
            );
        }

        if ($toRemove->isNotEmpty()) {
            $channel->memberUsers()->detach($toRemove->all());
        }
    }

    private function resolveAudienceUserIds(Project $project): Collection
    {
        $ids = collect($project->member_ids ?? [])->push($project->owner_id);

        if (!empty($project->departments)) {
            $ids = $ids->merge(
                Profile::whereIn('department_id', $project->departments)->pluck('user_id')
            );
        }

        return User::whereIn('id', $ids->filter()->unique())->pluck('id');
    }
}
