<?php

namespace App\Services\ProjectTask;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\Task;
use App\Models\User;

class EventSyncService
{
    private const DESCRIPTION_PREFIX = 'وظیفه مرتبط با سیستم برد وظایف #';

    private const DEFAULT_REMIND_HOURS = 24;

    public static function isTaskEvent(?string $description): bool
    {
        return $description !== null && str_starts_with($description, self::DESCRIPTION_PREFIX);
    }

    public static function taskIdFrom(?string $description): ?int
    {
        if (!self::isTaskEvent($description)) {
            return null;
        }

        return (int) substr($description, strlen(self::DESCRIPTION_PREFIX));
    }

    public static function descriptionPrefix(): string
    {
        return self::DESCRIPTION_PREFIX;
    }

    public function purge(Task $task): void
    {
        $this->deleteEvents($this->description($task));

        $this->bumpHeartbeat($task);
    }

    public function sync(Task $task): void
    {
        $task->loadMissing(['creator', 'assignee', 'detail']);

        $owner = $task->creator;

        if (!$task->deadline || $task->archived_at !== null || !$owner) {
            $this->purge($task);

            return;
        }

        $description = $this->description($task);

        $event = Event::updateOrCreate(
            [
                'user_id' => $owner->id,
                'description' => $description,
            ],
            [
                'title' => $task->title,
                'date' => $task->deadline,
                'private' => true,
                'remind_hours' => self::DEFAULT_REMIND_HOURS,
            ]
        );

        $this->pruneOtherOwners($description, $owner->id);

        $candidateIds = collect([$task->assigned_to, $task->detail?->responsible_user_id])
            ->merge($task->detail?->collaborators ?? [])
            ->filter()
            ->reject(fn($id) => (int) $id === $owner->id)
            ->unique();

        $recipientIds = User::active()->whereIn('id', $candidateIds)->pluck('id');

        $event->shares()
            ->whereNotIn('user_id', $recipientIds)
            ->get()
            ->each(fn(EventShare $share) => $share->delete());

        foreach ($recipientIds as $recipientId) {
            EventShare::firstOrCreate(
                ['event_id' => $event->id, 'user_id' => $recipientId],
                ['shared_by' => $owner->id]
            );
        }

        $this->bumpHeartbeat($task);
    }

    private function bumpHeartbeat(Task $task): void
    {
        if ($task->project_id) {
            ProjectHeartbeat::bump($task->project_id, 'task');
        }
    }

    private function pruneOtherOwners(string $description, int $keepUserId): void
    {
        $this->deleteEvents($description, $keepUserId);
    }

    private function deleteEvents(string $description, ?int $excludeUserId = null): void
    {
        Event::query()
            ->where('description', $description)
            ->when($excludeUserId, fn($q, $id) => $q->where('user_id', '!=', $id))
            ->get()
            ->each(fn(Event $event) => $event->delete());
    }

    private function description(Task $task): string
    {
        return self::DESCRIPTION_PREFIX . $task->id;
    }
}
