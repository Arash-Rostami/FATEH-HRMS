<?php

namespace App\Services\Menu\Notifications;

use App\Enums\TaskActivityType;
use App\Models\Project;
use App\Models\Reply;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class ProjectNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return "شما به پروژهٔ «{$subject->name}» اضافه شده‌اید؛ برای مشاهده کلیک کنید.";
    }

    public function for($subject): Collection
    {
        $addedIds = $this->resolveAddedIds($subject);

        return $addedIds->isEmpty() ? collect() : User::active()->whereIn('id', $addedIds)->get();
    }

    public function getKey(): string
    {
        return 'projects-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return true;
    }

    public function title($subject, User $user): string
    {
        return 'دعوت به پروژه: ' . $subject->name;
    }

    public function triggers(): array
    {
        return [
            ['class' => Project::class, 'on' => ['created'], 'subject' => null],
            ['class' => Reply::class, 'on' => ['created'], 'subject' => function (Reply $reply) {
                if ($reply->repliable_type !== Project::class || $reply->type !== TaskActivityType::Assignment) {
                    return null;
                }

                return empty($reply->payload['added'] ?? []) ? null : $reply->repliable;
            }],
        ];
    }

    public function url($subject): ?string
    {
        return route('projects', ['open' => $subject->getKey()]);
    }

    private function resolveAddedIds(Project $project): Collection
    {
        $latestReply = $project->replies()
            ->where('type', TaskActivityType::Assignment)
            ->latest('id')
            ->first();

        if ($latestReply) return collect($latestReply->payload['added'] ?? []);

        return collect($project->member_ids ?? [])
            ->reject(fn($id) => (int) $id === (int) $project->owner_id);
    }
}
