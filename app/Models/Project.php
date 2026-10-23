<?php

namespace App\Models;

use App\Enums\TaskActivityType;
use App\Jobs\SyncProjectChannelMembershipJob;
use App\Livewire\Dashboard\Project\Actions\CancelWorkflowAction;
use App\Livewire\Dashboard\Project\Actions\ForceDeleteProjectAction;
use App\Models\Concerns\HasMenuState;
use App\Models\Concerns\HasPrunableStatus;
use App\Models\Concerns\HasReminders;
use App\Models\Concerns\HasReplies;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ProjectTask\ActivityLogger;
use App\Support\ProjectCharter;
use App\Support\TaskAccessPolicy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\CalendarUtils;

class Project extends Model
{
    use HasFactory,
        HasMenuState,
        HasReplies,
        SoftDeletes,
        Prunable,
        HasPrunableStatus,
        HasReminders;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'member_ids',
        'departments',
        'channel_id',
        'settings',
        'archived_at',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public static function generateSlug(string $name): string
    {
        $base = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower(trim($name)));
        $base = trim(preg_replace('/-{2,}/u', '-', (string)$base), '-');

        return ($base !== '' ? $base : 'project') . '-' . Str::random(6);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->owner_id === $user->id || in_array($user->id, $this->member_ids ?? [], true)) {
            return true;
        }

        $departmentCode = $user->profile?->department_id;

        return filled($departmentCode) && in_array($departmentCode, $this->departments ?? [], true);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subDays($this->getPruneDays()));
    }

    public function prune(): void
    {
        app(ForceDeleteProjectAction::class)->execute($this);
    }

    public static function idForChannel(int $channelId): ?int
    {
        return static::withTrashed()->where('channel_id', $channelId)->value('id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('owner_id', $user->id)
                ->orWhereJsonContains('member_ids', $user->id);

            $departmentCode = $user->profile?->department_id;
            if (filled($departmentCode)) {
                $q->orWhere(fn(Builder $x) => $x->whereNotNull('departments')
                    ->whereRaw('JSON_LENGTH(departments) > 0')
                    ->whereJsonContains('departments', $departmentCode));
            }
        });
    }

    public const KNOWN_SETTING_KEYS = ['requires_approval', 'sla', 'deadline', 'custom_schema', 'labels', 'auto_archive_days'];

    public const SETTING_LABELS = [
        'escalation_days' => ['ارجاع تأخیر به جوابگو', 'روز'],
        'due_soon_hours' => ['هشدار نزدیک سررسید', 'ساعت'],
    ];

    private ?ProjectCharter $charter = null;

    public function charter(): ProjectCharter
    {
        return $this->charter ??= new ProjectCharter($this);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return ($this->settings ?? [])[$key] ?? $default;
    }

    public function deadlineCapExceeded(?Carbon $deadline): bool
    {
        $cap = $this->charter()->cap();

        return $cap !== null && $deadline !== null && $deadline->gt($cap);
    }

    public function settingsSummary(): string
    {
        $parts = [];

        if ($this->setting('requires_approval') === true) {
            $parts[] = 'تأیید مدیر فعال';
        }

        $sla = $this->setting('sla');
        if ($sla) {
            $parts[] = 'SLA: ' . $sla . ' ساعت';
        }

        $deadline = $this->setting('deadline');
        if ($deadline && ($timestamp = strtotime($deadline))) {
            $parts[] = 'مهلت: ' . CalendarUtils::strftime('Y/m/d', $timestamp);
        }

        $schema = $this->setting('custom_schema');
        if (is_array($schema) && $schema !== []) {
            $parts[] = count($schema) . ' فیلد سفارشی';
        }

        return $parts !== [] ? implode('، ', $parts) : '—';
    }

    public function otherSettings(): array
    {
        $out = [];

        foreach ($this->settings ?? [] as $key => $value) {
            if (in_array($key, self::KNOWN_SETTING_KEYS, true) || $value === null || $value === '') {
                continue;
            }

            $out[$key] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $out;
    }

    public function otherSettingChips(): array
    {
        $chips = [];

        foreach ($this->otherSettings() as $key => $value) {
            [$label, $unit] = self::SETTING_LABELS[$key] ?? [$key, null];
            $chips[$key] = ['label' => $label, 'text' => trim($value . ' ' . $unit)];
        }

        return $chips;
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }

    public static function pruneSettings(array $settings): array
    {
        return array_filter($settings, fn($v) => $v !== null && $v !== false && $v !== '' && $v !== []);
    }

    protected static function booted(): void
    {
        static::saving(function (self $project) {
            if ($project->isDirty('settings') && is_array($project->settings)) {
                $project->settings = static::pruneSettings($project->settings) ?: null;
            }

            if ($project->isDirty('member_ids')) {
                $project->member_ids = TaskAccessPolicy::assignableIds($project->member_ids ?? []);
            }

            if (!$project->isDirty('owner_id')) return;

            if ($project->owner_id && !User::visibleOnBoard()->whereKey($project->owner_id)->exists()) {
                throw ValidationException::withMessages(['owner_id' => 'مالک جدید باید عضو فعال این پروژه باشد.']);
            }

            if ($previousOwner = $project->getOriginal('owner_id')) {
                $project->member_ids = array_values(array_unique([...($project->member_ids ?? []), $previousOwner]));
            }
        });

        static::saved(function (self $project) {
            $project->charter = null;
            $project->syncChannelMembershipIfNeeded();
            $project->logCreationIfNeeded();
            $project->logMembershipChangesIfNeeded();
            $project->logSettingsChangesIfNeeded();
            $project->handleOwnerChangeIfNeeded();
            $project->releaseRemovedMembersIfNeeded();
            $project->approveDoneTasksIfApprovalDisabled();
            $project->syncTaskDetailProjectName();
        });

        static::deleting(function (self $project) {
            if (!$project->isForceDeleting()) {
                $cancel = app(CancelWorkflowAction::class);

                Workflow::forProject($project->id)
                    ->whereIn('status', [Workflow::STATUS_ACTIVE, Workflow::STATUS_DRAFT])
                    ->get()
                    ->each(fn (Workflow $wf) => $cancel->cancel($wf, 'project_deleted'));
            }
        });

        static::restored(fn (self $project) => app(CancelWorkflowAction::class)->revive($project));
    }

    protected function casts(): array
    {
        return [
            'member_ids' => 'array',
            'departments' => 'array',
            'settings' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    protected function logCreationIfNeeded(): void
    {
        if (!$this->wasRecentlyCreated || !$this->owner_id) {
            return;
        }

        app(ActivityLogger::class)->system($this, auth()->user(), TaskActivityType::SettingsChange, [
            'created' => true,
            'owner_name' => auth()->id() === $this->owner_id ? auth()->user()->name : User::whereKey($this->owner_id)->value('name'),
        ]);
    }

    protected function syncTaskDetailProjectName(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('name')) {
            return;
        }

        TaskDetail::whereIn('task_id', Task::withTrashed()->where('project_id', $this->id)->select('id'))->update(['project' => $this->name]);
        ModelCacheVersion::bump(TaskDetail::class);
    }

    protected function approveDoneTasksIfApprovalDisabled(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('settings')) {
            return;
        }

        if (($this->getOriginal('settings')['requires_approval'] ?? null) !== true || $this->setting('requires_approval') === true) {
            return;
        }

        $this->tasks()->where('status', 'done')->whereNull('approved_at')->get()->each(function (Task $task) {
            $task->systemApproval = true;
            $task->update(['approved_at' => now(), 'approved_by' => null]);
        });
    }

    protected function logSettingsChangesIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('settings')) {
            return;
        }

        $before = $this->getOriginal('settings') ?? [];
        $after = $this->settings ?? [];

        $changed = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        if ($changed === []) {
            return;
        }

        app(ActivityLogger::class)->system($this, auth()->user(), TaskActivityType::SettingsChange, ['changed' => $changed]);
    }

    protected function logMembershipChangesIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged(['member_ids', 'departments'])) {
            return;
        }

        $payload = [];

        if ($this->wasChanged('member_ids')) {
            $before = $this->getOriginal('member_ids') ?? [];
            $after = $this->member_ids ?? [];
            $previousOwner = $this->wasChanged('owner_id') ? [$this->getOriginal('owner_id')] : [];
            $payload['added'] = array_values(array_diff($after, $before, $previousOwner));
            $payload['removed'] = array_values(array_diff($before, $after));
        }

        if ($this->wasChanged('departments')) {
            $before = $this->getOriginal('departments') ?? [];
            $after = $this->departments ?? [];
            $payload['added_departments'] = array_values(array_diff($after, $before));
            $payload['removed_departments'] = array_values(array_diff($before, $after));
        }

        if (array_filter($payload) === []) {
            return;
        }

        app(ActivityLogger::class)->system(
            $this,
            auth()->user(),
            TaskActivityType::Assignment,
            $payload
        );
    }

    public function releaseUsers(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        DB::transaction(function () use ($userIds) {
            $this->tasks()
                ->whereIn('assigned_to', $userIds)
                ->where('status', '!=', 'done')
                ->whereNull('archived_at')
                ->get()
                ->each->update(['assigned_to' => null]);

            static::stripTaskPeople($this->tasks(), $userIds);
            static::stripWorkflowAssignees($this->workflows(), $userIds);
        });
    }

    public static function stripTaskPeople($tasks, array $userIds): void
    {
        TaskDetail::whereIn('task_id', $tasks->where('status', '!=', 'done')->whereNull('archived_at')->select('id'))
            ->where(function (Builder $query) use ($userIds) {
                $query->whereIn('responsible_user_id', $userIds);

                foreach ($userIds as $id) {
                    $query->orWhereJsonContains('collaborators', $id);
                }
            })
            ->get()
            ->each(fn(TaskDetail $detail) => $detail->update([
                'collaborators' => array_values(array_diff($detail->collaborators ?? [], $userIds)),
                'responsible_user_id' => in_array($detail->responsible_user_id, $userIds) ? null : $detail->responsible_user_id,
            ]));
    }

    public static function stripWorkflowAssignees($workflows, array $userIds): void
    {
        $workflows->whereIn('status', [Workflow::STATUS_ACTIVE, Workflow::STATUS_DRAFT])->get()
            ->each(function (Workflow $workflow) use ($userIds) {
                $changed = false;

                $steps = array_map(function (array $step) use ($userIds, &$changed) {
                    $current = $step['assignee_user_ids'] ?? [];
                    $kept = array_values(array_diff($current, $userIds));

                    if ($kept === array_values($current)) {
                        return $step;
                    }

                    $changed = true;

                    return [...$step, 'assignee_user_ids' => $kept];
                }, $workflow->steps ?? []);

                if ($changed) {
                    $workflow->update(['steps' => $steps]);
                }
            });
    }

    public function ensureActiveOwner(): void
    {
        if ($this->owner_id && User::active()->whereKey($this->owner_id)->exists()) {
            return;
        }

        $successor = $this->charter()->rosterQuery()
            ->with('profile:user_id,position')
            ->whereIn('id', $this->member_ids ?? [])
            ->get()
            ->sortBy(fn(User $user) => [$user->rank(), $user->id])
            ->first();

        if ($successor) {
            $this->update(['owner_id' => $successor->id]);
        }
    }

    protected function handleOwnerChangeIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('owner_id')) {
            return;
        }

        DB::transaction(fn() => $this->workflows()
            ->whereNotIn('status', [Workflow::STATUS_COMPLETED, Workflow::STATUS_CANCELLED])
            ->get()
            ->each->update(['owner_id' => $this->owner_id]));

        app(ActivityLogger::class)->system($this, auth()->user(), TaskActivityType::SettingsChange, [
            'owner_id' => $this->owner_id,
            'owner_name' => User::whereKey($this->owner_id)->value('name'),
        ]);
    }

    protected function releaseRemovedMembersIfNeeded(): void
    {
        if ($this->wasRecentlyCreated || !$this->wasChanged('member_ids')) {
            return;
        }

        $removed = array_diff($this->getOriginal('member_ids') ?? [], $this->member_ids ?? [], [$this->owner_id]);

        if ($removed === []) {
            return;
        }

        $this->releaseUsers(
            User::with('profile:user_id,department_id')
                ->whereIn('id', $removed)
                ->get()
                ->reject(fn(User $user) => $this->isVisibleTo($user))
                ->pluck('id')
                ->all()
        );
    }

    protected function syncChannelMembershipIfNeeded(): void
    {
        $membershipRelevant = $this->wasRecentlyCreated
            || $this->wasChanged(['member_ids', 'departments', 'owner_id']);

        if ($membershipRelevant) {
            SyncProjectChannelMembershipJob::dispatch($this->id);
        }
    }
}
