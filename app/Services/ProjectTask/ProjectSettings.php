<?php

namespace App\Services\ProjectTask;

use App\Livewire\Dashboard\Project\Forms\ProjectForm;
use App\Models\Project;
use App\Support\ProjectCharter;
use App\Traits\ResolvesTaskDeadline;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Morilog\Jalali\CalendarUtils;

final class ProjectSettings
{
    use ResolvesTaskDeadline;

    public function bag(ProjectForm $form, ?string $currentDeadline = null): array
    {
        $bag = Project::pruneSettings([
            'requires_approval' => $form->requiresApproval,
            'sla' => $form->slaHours,
            'deadline' => $this->guardedDeadline($form, $currentDeadline),
            'labels' => ProjectCharter::normalizeLabels($form->labels),
            'auto_archive_days' => $form->autoArchiveDays,
        ]);

        if ($form->customSchema !== []) {
            $bag['custom_schema'] = collect($form->customSchema)
                ->mapWithKeys(fn($item) => [$item['key'] => ['label' => $item['label']]])
                ->all();
        }

        foreach ($form->extraSettings as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($key !== '' && $value !== '' && !in_array($key, Project::KNOWN_SETTING_KEYS, true)) {
                $bag[$key] = $value;
            }
        }

        return $bag;
    }

    private function guardedDeadline(ProjectForm $form, ?string $currentDeadline): ?string
    {
        $filled = count(array_filter([$form->deadlineYear, $form->deadlineMonth, $form->deadlineDay]));

        if ($filled === 0) return null;

        if ($filled < 3) {
            $this->failDeadline('تاریخ مهلت را کامل وارد کنید.');
        }

        try {
            $date = $this->resolveDeadline($form)->toDateString();
        } catch (InvalidArgumentException) {
            $this->failDeadline('تاریخ واردشده معتبر نیست.');
        }

        if ($date !== $currentDeadline && $date < today()->toDateString()) {
            $this->failDeadline('مهلت پروژه نمی‌تواند گذشته باشد.');
        }

        return $date;
    }

    private function failDeadline(string $message): never
    {
        throw ValidationException::withMessages(['projectForm.deadlineDay' => $message]);
    }

    public function mergeExtra(array $settings, array $rows): array
    {
        $out = array_intersect_key($settings, array_flip(Project::KNOWN_SETTING_KEYS));

        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($key !== '' && $value !== '' && !in_array($key, Project::KNOWN_SETTING_KEYS, true)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public function fillForm(ProjectForm $form, Project $project): void
    {
        $settings = $project->settings ?? [];

        $form->requiresApproval = (bool)($settings['requires_approval'] ?? false);
        $form->slaHours = $settings['sla'] ?? null;
        $form->labels = $settings['labels'] ?? [];
        $form->autoArchiveDays = $settings['auto_archive_days'] ?? null;
        $form->customSchema = collect($settings['custom_schema'] ?? [])
            ->map(fn($item, $key) => ['key' => $key, 'label' => $item['label'] ?? $key])
            ->values()
            ->all();
        $form->extraSettings = collect($project->otherSettings())
            ->map(fn($value, $key) => ['key' => $key, 'value' => $value])
            ->values()
            ->all();

        $form->deadlineYear = '';
        $form->deadlineMonth = '';
        $form->deadlineDay = '';

        if ($deadline = $settings['deadline'] ?? null) {
            [$form->deadlineYear, $form->deadlineMonth, $form->deadlineDay] =
                CalendarUtils::toJalali(...array_map('intval', explode('-', $deadline)));
        }
    }
}