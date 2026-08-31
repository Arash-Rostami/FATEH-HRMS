<?php

namespace App\Services\ProjectTask;

use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChannelProvisioner
{
    public function resolve(Project $project): Channel
    {
        $existing = $project->channel_id ? Channel::find($project->channel_id) : null;

        return $existing ?? $this->provision($project);
    }

    private function provision(Project $project): Channel
    {
        return DB::transaction(function () use ($project) {
            $channel = Channel::create([
                'name' => mb_substr($project->name, 0, 100),
                'slug' => Project::generateSlug($project->name),
                'description' => null,
                'type' => ChannelType::Private->value,
                'owner_id' => $project->owner_id,
            ]);

            $this->syncMembers($channel, $project);

            $project->update(['channel_id' => $channel->id]);

            return $channel;
        });
    }

    private function syncMembers(Channel $channel, Project $project): void
    {
        $memberIds = collect($project->member_ids ?? [])
            ->filter()
            ->unique()
            ->reject(fn($id) => (int) $id === (int) $project->owner_id);

        if ($memberIds->isEmpty()) {
            return;
        }

        $existingUserIds = User::whereIn('id', $memberIds)->pluck('id');

        $channel->memberUsers()->syncWithoutDetaching(
            $existingUserIds->mapWithKeys(fn($id) => [$id => [
                'joined_at' => now(),
                'entered_at' => null,
                'last_read_message_id' => null,
            ]])->all()
        );
    }
}
