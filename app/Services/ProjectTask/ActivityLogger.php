<?php

namespace App\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Jobs\ReconcileNudge;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Menu\Notifications\TaskNudge;
use App\Services\ProjectTask\Renderers\ApprovalRenderer;
use App\Services\ProjectTask\Renderers\ArchiveRenderer;
use App\Services\ProjectTask\Renderers\AssignmentRenderer;
use App\Services\ProjectTask\Renderers\AttachmentRenderer;
use App\Services\ProjectTask\Renderers\CommentRenderer;
use App\Services\ProjectTask\Renderers\DeadlineChangeRenderer;
use App\Services\ProjectTask\Renderers\DepartmentChangeRenderer;
use App\Services\ProjectTask\Renderers\LabelChangeRenderer;
use App\Services\ProjectTask\Renderers\MetaChangeRenderer;
use App\Services\ProjectTask\Renderers\PriorityChangeRenderer;
use App\Services\ProjectTask\Renderers\ProjectChangeRenderer;
use App\Services\ProjectTask\Renderers\ResponsibleChangeRenderer;
use App\Services\ProjectTask\Renderers\SettingsChangeRenderer;
use App\Services\ProjectTask\Renderers\StateChangeRenderer;
use App\Services\ProjectTask\Renderers\StatusChangeRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    private const RENDERERS = [
        'comment' => CommentRenderer::class,
        'status_change' => StatusChangeRenderer::class,
        'assignment' => AssignmentRenderer::class,
        'archive' => ArchiveRenderer::class,
        'approval' => ApprovalRenderer::class,
        'attachment' => AttachmentRenderer::class,
        'responsible_change' => ResponsibleChangeRenderer::class,
        'department_change' => DepartmentChangeRenderer::class,
        'state_change' => StateChangeRenderer::class,
        'deadline_change' => DeadlineChangeRenderer::class,
        'priority_change' => PriorityChangeRenderer::class,
        'label_change' => LabelChangeRenderer::class,
        'project_change' => ProjectChangeRenderer::class,
        'meta_change' => MetaChangeRenderer::class,
        'settings_change' => SettingsChangeRenderer::class,
    ];

    private static array $rendererInstances = [];

    public function comment(Model $repliable, User $user, string $body, array $files = []): Reply
    {
        $reply = $repliable->addReply($user, $body, $files);

        $type = (trim($body) === '' && $files !== []) ? TaskActivityType::Attachment : TaskActivityType::Comment;
        $reply->update(['type' => $type]);

        $this->bumpHeartbeat($repliable);

        return $reply;
    }

    public function feedFor(Project $project): Builder
    {
        return Reply::query()
            ->where(function (Builder $query) use ($project) {
                $query->where(function (Builder $q) use ($project) {
                    $q->where('repliable_type', Task::class)
                        ->whereIn('repliable_id', Task::query()->select('id')->where('project_id', $project->id));
                })->orWhere(function (Builder $q) use ($project) {
                    $q->where('repliable_type', Project::class)
                        ->where('repliable_id', $project->id);
                });
            })
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function logMappedChanges(Model $repliable, Model $source, ?User $actor, array $activityMap): void
    {
        $now = now();
        $rows = [];

        foreach (array_keys($source->getChanges()) as $field) {
            $type = $activityMap[$field] ?? null;
            if (!$type) continue;

            $payload = $this->resolvePayload($field, $source->getOriginal($field), $source->getAttribute($field));
            if ($payload === null) continue;

            $rows[] = [
                'repliable_type' => $repliable->getMorphClass(),
                'repliable_id' => $repliable->getKey(),
                'user_id' => $actor?->id,
                'body' => null,
                'files' => json_encode([]),
                'type' => $type->value,
                'payload' => json_encode($payload),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) return;

        Reply::insert($rows);
        ModelCacheVersion::bump(Reply::class);
        $this->bumpHeartbeat($repliable, affectsTaskDomain: true);

        if ($repliable instanceof Task && !($source instanceof Task)) {
            dispatch(new ReconcileNudge((new TaskNudge())->getKey(), Task::class, $repliable->id))->afterCommit();
        }
    }

    public function render(Reply $reply): array
    {
        $class = self::RENDERERS[$reply->type?->value ?? TaskActivityType::Comment->value];
        $renderer = self::$rendererInstances[$class] ??= app($class);

        return [
            'icon' => $renderer->getIcon($reply),
            'label' => $renderer->getLabel(),
            'body' => $renderer->getBody($reply),
        ];
    }

    public function system(Model $repliable, ?User $actor, TaskActivityType $type, array $payload = []): Reply
    {
        $reply = $repliable->replies()->create([
            'user_id' => $actor?->id,
            'body' => null,
            'files' => [],
            'type' => $type,
            'payload' => $payload,
        ]);

        $this->bumpHeartbeat($repliable, affectsTaskDomain: true);

        return $reply;
    }

    private function bumpHeartbeat(Model $repliable, bool $affectsTaskDomain = false): void
    {
        $projectId = $repliable instanceof Project ? $repliable->id : ($repliable instanceof Task ? $repliable->project_id : null);

        if (!$projectId) return;

        ProjectHeartbeat::bump($projectId, 'activity');

        if ($affectsTaskDomain && $repliable instanceof Task) {
            ProjectHeartbeat::bump($projectId, 'task');
        }
    }

    private function resolvePayload(string $field, mixed $before, mixed $after): ?array
    {
        if ($field === 'collaborators' || $field === 'labels') {
            $added = array_values(array_diff($after ?? [], $before ?? []));
            $removed = array_values(array_diff($before ?? [], $after ?? []));

            return ($added === [] && $removed === []) ? null : ['added' => $added, 'removed' => $removed];
        }

        if ($field === 'archived_at') {
            return ['archived' => $after !== null];
        }

        if ($field === 'approved_at') {
            return ['approved' => $after !== null];
        }

        if ($field === 'meta') {
            $added = array_keys(array_diff_key($after ?? [], $before ?? []));
            $removed = array_keys(array_diff_key($before ?? [], $after ?? []));
            $changed = [];

            foreach (array_intersect_key($after ?? [], $before ?? []) as $key => $value) {
                if ($value !== $before[$key]) {
                    $changed[] = $key;
                }
            }

            return ($added === [] && $removed === [] && $changed === []) ? null : ['added' => $added, 'removed' => $removed, 'changed' => $changed];
        }

        $from = $before instanceof \BackedEnum ? $before->value : ($before instanceof \DateTimeInterface ? $before->toDateString() : $before);
        $to = $after instanceof \BackedEnum ? $after->value : ($after instanceof \DateTimeInterface ? $after->toDateString() : $after);

        return ['from' => $from, 'to' => $to];
    }
}
