<?php

namespace App\Services\Menu\Toasts;

use App\Enums\TaskActivityType;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Project;
use App\Models\Reply;
use App\Models\User;
use App\Services\Menu\Contracts\MenuEdge;
use Illuminate\Support\Collection;

class ProjectToast implements MenuEdge
{
    private array $added = [];

    public function getKey(): string
    {
        return 'projects-controller:edge';
    }

    public function for($subject): Collection
    {
        $this->added = array_flip($this->resolveAddedIds($subject));
        $unopened = $subject->channel_id ? array_flip(Channel::invitedUserIds($subject->channel_id)) : [];

        $ids = array_unique([...array_keys($this->added), ...array_keys($unopened)]);

        return $ids === [] ? collect() : User::active()->whereIn('id', $ids)->get();
    }

    public function title($subject, User $user): string
    {
        if (isset($this->added[$user->id])) {
            return 'دعوت به پروژه: ' . $subject->name;
        }

        return 'هنوز فضای کاری زیر را باز نکرده‌اید';
    }

    public function body($subject, User $user): string
    {
        return $subject->name;
    }

    public function icon($subject, User $user): string
    {
        return isset($this->added[$user->id]) ? 'group_add' : 'workspaces';
    }

    public function url($subject): ?string
    {
        return route('projects', ['open' => $subject->getKey()]);
    }

    public function triggers(): array
    {
        return [
            ['class' => Project::class, 'on' => ['created', 'deleted', 'forceDeleted'], 'subject' => null],
            ['class' => Reply::class, 'on' => ['created'], 'subject' => function (Reply $reply) {
                if ($reply->repliable_type !== Project::class || $reply->type !== TaskActivityType::Assignment) {
                    return null;
                }

                return empty($reply->payload['added'] ?? []) ? null : $reply->repliable;
            }],
            ['class' => ChannelMember::class, 'on' => ['created', 'deleted'], 'subject' => fn(ChannelMember $cm) => $this->projectForChannel($cm->channel_id)],
            ['class' => ChannelMember::class, 'on' => ['updated'], 'subject' => fn(ChannelMember $cm) => $cm->wasChanged('entered_at') ? $this->projectForChannel($cm->channel_id) : null],
        ];
    }

    private function projectForChannel(int $channelId): ?Project
    {
        return Project::where('channel_id', $channelId)->first();
    }

    private function resolveAddedIds(Project $project): array
    {
        $reply = $project->replies()
            ->where('type', TaskActivityType::Assignment)
            ->latest('id')
            ->first(['payload']);

        if ($reply) {
            return $reply->payload['added'] ?? [];
        }

        return array_filter(
            $project->member_ids ?? [],
            fn($id) => (int) $id !== (int) $project->owner_id
        );
    }
}
