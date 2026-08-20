<?php

namespace App\Livewire\Dashboard\TaskBoard\Presentation;

use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use Illuminate\Support\ViewErrorBag;

class TaskBoardPresenter
{
    public function columnConfig(): array
    {
        return [
            'todo'        => ['title' => TaskStatus::Todo->getLabel(),       'icon' => '🧾', 'color' => 'primary',  'lightGradient' => 'from-rose-500 to-pink-600',    'darkGradient' => 'from-rose-700 to-pink-800'],
            'in-progress' => ['title' => TaskStatus::InProgress->getLabel(), 'icon' => '⏳', 'color' => 'secondary', 'lightGradient' => 'from-amber-500 to-orange-600',  'darkGradient' => 'from-amber-700 to-orange-800'],
            'pending'     => ['title' => TaskStatus::Pending->getLabel(),    'icon' => '🛑', 'color' => 'error', 'lightGradient' => 'from-red-600 to-red-700', 'darkGradient' => 'from-red-800 to-red-900'],
            'done'        => ['title' => TaskStatus::Done->getLabel(),       'icon' => '🎯', 'color' => 'tertiary',  'lightGradient' => 'from-emerald-500 to-green-600', 'darkGradient' => 'from-emerald-700 to-green-800'],
        ];
    }

    public function columnState(string $status): array
    {
        return $this->columnConfig()[$status] ?? $this->columnConfig()['todo'];
    }

    public function emptyStateFlags(string $column, bool $showAllDone, bool $showArchived, string $search, array $doneTotalCount): array
    {
        $windowActive = $column === 'done' && !$showAllDone && !$showArchived && $search === '';
        return [
            'windowActive' => $windowActive,
            'olderExist' => $windowActive && (($doneTotalCount['done'] ?? 0) > 0),
            'archiveEmpty' => $column === 'done' && $showArchived && $search === '',
        ];
    }

    public function defaultTaskFormTab(array $tabs, ViewErrorBag $errors): string
    {
        foreach ($tabs as $tab) {
            if ($errors->hasAny($tab['errors'])) {
                return $tab['key'];
            }
        }

        return $tabs[0]['key'] ?? 'content';
    }

    public function taskFormTabs(): array
    {
        return [
            [
                'key' => 'content',
                'label' => 'محتوای وظیفه',
                'icon' => 'edit_note',
                'errors' => ['form.newTitle', 'form.deadlineYear', 'form.deadlineMonth', 'form.deadlineDay', 'form.deadline', 'form.selectedAssignee'],
            ],
            [
                'key' => 'classification',
                'label' => 'اطلاعات سازمانی',
                'icon' => 'corporate_fare',
                'errors' => ['form.departmentId', 'form.unit', 'form.section', 'form.project', 'form.scheme', 'form.responsibleUserId', 'form.collaborators'],
            ],
            [
                'key' => 'action',
                'label' => 'اقدام و مستندات',
                'icon' => 'flag',
                'errors' => ['form.actionSourceDomain', 'form.actionSource', 'form.state', 'form.attachments'],
            ],
            [
                'key' => 'reply',
                'label' => 'گفتگو',
                'icon' => 'forum',
                'errors' => ['taskReplyForm.body', 'taskReplyForm.files'],
            ],
        ];
    }
}
