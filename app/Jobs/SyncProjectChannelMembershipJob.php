<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\ProjectTask\ChannelProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncProjectChannelMembershipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(public int $projectId)
    {
        $this->afterCommit();
    }

    public function handle(ChannelProvisioner $provisioner): void
    {
        $project = Project::find($this->projectId);

        if (!$project) return;

        $channel = $provisioner->resolve($project);

        $ownerChanged = $project->owner_id && $channel->owner_id !== $project->owner_id;

        if ($ownerChanged) {
            $channel->update(['owner_id' => $project->owner_id]);
        }

        $targetIds = collect($project->charter()->rosterIds());
        $currentIds = $channel->memberUsers()->pluck('users.id');

        $toAdd = $targetIds->diff($currentIds);
        $toRemove = $currentIds->diff($targetIds)->reject(fn($id) => (int) $id === (int) $project->owner_id);

        if ($toAdd->isNotEmpty()) {
            $channel->memberUsers()->syncWithoutDetaching(
                $toAdd->mapWithKeys(fn($id) => [$id => [
                    'joined_at' => now(),
                    'entered_at' => (int) $id === (int) $project->owner_id ? now() : null,
                    'last_read_message_id' => null,
                ]])->all()
            );
        }

        if ($ownerChanged && $currentIds->contains($project->owner_id)) {
            DB::table('channel_members')
                ->where('channel_id', $channel->id)
                ->where('user_id', $project->owner_id)
                ->whereNull('entered_at')
                ->update(['entered_at' => now()]);
        }

        if ($toRemove->isNotEmpty()) {
            $channel->memberUsers()->detach($toRemove->all());
        }
    }
}
