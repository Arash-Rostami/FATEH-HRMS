<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class ProjectCharter
{
    private ?array $rosterIds = null;

    public function __construct(private Project $project)
    {
    }

    public static function normalizeLabels(array $labels): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($label) => trim(str_replace(['ي', 'ك'], ['ی', 'ک'], (string) $label)),
            $labels
        ), fn(string $label) => $label !== '')));
    }

    public function rosterQuery(): Builder
    {
        $ids = array_values(array_filter([$this->project->owner_id, ...($this->project->member_ids ?? [])]));
        $departments = $this->project->departments ?? [];

        return User::visibleOnBoard()->where(function (Builder $query) use ($ids, $departments) {
            $query->whereIn('id', $ids);

            if ($departments !== []) {
                $query->orWhereHas('profile', fn(Builder $profile) => $profile->whereIn('department_id', $departments));
            }
        });
    }

    public function rosterIds(): array
    {
        return $this->rosterIds ??= $this->rosterQuery()->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    public function rosterOptions(): array
    {
        return $this->rosterQuery()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function isMember(int $userId): bool
    {
        return in_array($userId, $this->rosterIds(), true);
    }

    public function cap(): ?Carbon
    {
        $cap = $this->project->setting('deadline');

        return $cap ? Carbon::parse($cap)->endOfDay() : null;
    }

    public function slaHours(): ?int
    {
        $sla = $this->project->setting('sla');

        return $sla ? (int) $sla : null;
    }

    public function defaultDeadline(?Carbon $from = null): ?Carbon
    {
        $sla = $this->slaHours();

        return $sla ? $this->clamp(($from ?? now())->copy()->addHours($sla)) : null;
    }

    public function clamp(?Carbon $deadline): ?Carbon
    {
        $cap = $this->cap();

        return $deadline && $cap && $deadline->gt($cap) ? $cap->copy()->setTime(12, 0) : $deadline;
    }

    public function assertDeadline(?Carbon $deadline, ?Carbon $current, string $key): void
    {
        if ($deadline === null || $current?->isSameDay($deadline)) {
            return;
        }

        $cap = $this->cap();

        if ($cap && $deadline->gt($cap)) {
            throw ValidationException::withMessages([$key => __('resources/task/strings.validation.project_deadline_cap')]);
        }
    }

    public function labels(): array
    {
        return (array) $this->project->setting('labels', []);
    }

    public function assertLabels(array $new, array $old, string $key): void
    {
        $vocabulary = $this->labels();

        if ($vocabulary === []) {
            return;
        }

        $added = array_diff(self::normalizeLabels($new), self::normalizeLabels($old));

        if (array_diff($added, $vocabulary) !== []) {
            throw ValidationException::withMessages([$key => 'برچسب انتخاب‌شده در فهرست برچسب‌های مجاز پروژه نیست.']);
        }
    }

    public function requiresApproval(): bool
    {
        return $this->project->setting('requires_approval') === true;
    }

    public function customSchema(): array
    {
        $schema = $this->project->setting('custom_schema');

        return is_array($schema) ? $schema : [];
    }

    public function autoArchiveDays(): int
    {
        return (int) ($this->project->setting('auto_archive_days') ?: 45);
    }

    public function escalationDays(): int
    {
        $days = $this->project->setting('escalation_days');

        return is_numeric($days) ? max(0, (int) $days) : 3;
    }

    public function dueSoonHours(): int
    {
        $hours = $this->project->setting('due_soon_hours');

        return is_numeric($hours) && (int) $hours > 0 ? (int) $hours : 24;
    }

    public function defaultDepartment(): ?string
    {
        $departments = $this->project->departments ?? [];

        return count($departments) === 1 ? (string) reset($departments) : null;
    }
}
