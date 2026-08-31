<?php

namespace App\Services\ProjectTask;

use App\Livewire\Dashboard\Project\Forms\ProjectForm;
use App\Models\Project;
use App\Traits\ResolvesTaskDeadline;
use Morilog\Jalali\CalendarUtils;

final class ProjectSettings
{
    use ResolvesTaskDeadline;

    public function bag(ProjectForm $form): array
    {
        $bag = array_filter([
            'requires_approval' => $form->requiresApproval,
            'sla' => $form->slaHours,
            'deadline' => $this->resolveDeadline($form)?->toDateString(),
        ], fn($v) => $v !== null && $v !== false && $v !== '');

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

    public function fillForm(ProjectForm $form, Project $project): void
    {
        $settings = $project->settings ?? [];

        $form->requiresApproval = (bool)($settings['requires_approval'] ?? false);
        $form->slaHours = $settings['sla'] ?? null;
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