<?php

namespace App\Traits;

use App\Livewire\Dashboard\Project\Actions\CancelWorkflowAction;
use App\Models\Workflow;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

trait FilamentWorkflowPresenter
{
    public static function getModelLabel(): string
    {
        return __('resources/project/strings.workflow.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources/project/strings.workflow.plural_label');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('resources/project/strings.workflow.plural_label');
    }

    public static function workflowColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('resources/project/strings.workflow.fields.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->label(__('resources/project/strings.workflow.fields.status'))
                ->badge()
                ->formatStateUsing(fn($state) => __('resources/project/strings.workflow.status.' . $state))
                ->color(fn($state) => self::statusColor($state))
                ->sortable(),
            TextColumn::make('progress')
                ->label(__('resources/project/strings.fields.progress'))
                ->html()
                ->state(fn(Workflow $record) => self::renderProgress($record)),
        ];
    }

    public static function workflowTimestampColumns(): array
    {
        return [
            TextColumn::make('started_at')
                ->label(__('resources/project/strings.workflow.fields.started_at'))
                ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('completed_at')
                ->label(__('resources/project/strings.workflow.fields.completed_at'))
                ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function cancelWorkflowAction(): Action
    {
        return Action::make('cancel')
            ->label(__('resources/project/strings.workflow.actions.cancel'))
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->iconButton()
            ->visible(fn(Workflow $record) => $record->isActive())
            ->requiresConfirmation()
            ->modalHeading(__('resources/project/strings.workflow.actions.cancel_confirm'))
            ->action(function (Workflow $record) {
                app(CancelWorkflowAction::class)->execute($record);

                Notification::make()
                    ->title(__('resources/project/strings.workflow.actions.cancel_success'))
                    ->success()
                    ->send();
            });
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->hiddenLabel()
                ->schema([
                    TextEntry::make('name')
                        ->label(__('resources/project/strings.workflow.fields.name')),
                    TextEntry::make('status')
                        ->label(__('resources/project/strings.workflow.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn($state) => __('resources/project/strings.workflow.status.' . $state))
                        ->color(fn($state) => self::statusColor($state)),
                    TextEntry::make('steps')
                        ->label(__('resources/project/strings.workflow.fields.steps'))
                        ->html()
                        ->columnSpanFull()
                        ->getStateUsing(fn(Workflow $record) => self::renderSteps($record)),
                    TextEntry::make('step_log')
                        ->label(__('resources/project/strings.workflow.fields.step_log'))
                        ->html()
                        ->columnSpanFull()
                        ->getStateUsing(fn(Workflow $record) => self::renderStepLog($record)),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    private static function statusColor(?string $status): string
    {
        return match ($status) {
            Workflow::STATUS_ACTIVE => 'warning',
            Workflow::STATUS_COMPLETED => 'success',
            Workflow::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    private static function renderProgress(Workflow $record): string
    {
        if ($record->current_step === null) {
            return '—';
        }

        $total = count($record->steps ?? []);
        $done = $record->isCompleted() ? $total : $record->current_step;
        $percent = $total > 0 ? (int)round($done / $total * 100) : 0;

        return <<<HTML
            <div style="display:flex;align-items:center;gap:8px;width:96px;">
                <div style="flex:1;height:6px;border-radius:9999px;background:rgba(148,163,184,0.35);overflow:hidden;">
                    <div style="height:100%;border-radius:9999px;background:#6366f1;width:{$percent}%;"></div>
                </div>
                <span style="font-size:11px;white-space:nowrap;">{$done}/{$total}</span>
            </div>
            HTML;
    }

    private static function renderSteps(Workflow $record): string
    {
        $steps = $record->steps ?? [];

        if (!$steps) {
            return '—';
        }

        $ids = array_unique(array_merge(...array_map(fn($step) => $step['assignee_user_ids'] ?? [], $steps)));
        $names = Workflow::resolveUserNames($ids);

        $items = collect($steps)->map(function (array $step, int $index) use ($names, $record) {
            $assignees = collect($step['assignee_user_ids'] ?? [])
                ->map(fn($id) => e($names[$id] ?? 'کاربر حذف‌شده'))
                ->implode('، ');
            $type = __('resources/project/strings.workflow.step_type.' . ($step['type'] ?? 'action'));
            $weight = $record->current_step === $index ? 'font-weight:700;' : '';

            return '<li style="' . $weight . '">' . e($step['label'] ?? '') .
                ' <span style="opacity:.6;font-size:11px;">(' . e($type) . ' · ' . $assignees . ')</span></li>';
        })->implode('');

        return '<ol style="margin:0;padding-inline-start:20px;">' . $items . '</ol>';
    }

    private static function renderStepLog(Workflow $record): string
    {
        $log = $record->step_log ?? [];

        if (!$log) {
            return '—';
        }

        $ids = array_unique(array_column($log, 'completed_by'));
        $names = Workflow::resolveUserNames($ids);

        $items = collect($log)->map(function (array $entry) use ($names, $record) {
            $stepLabel = $record->steps[$entry['step']]['label'] ?? '—';
            $who = e($names[$entry['completed_by']] ?? 'کاربر حذف‌شده');
            $when = !empty($entry['completed_at']) ? toJalali($entry['completed_at'], 'Y/m/d H:i') : '—';

            $extra = [];
            if (array_key_exists('approved', $entry)) {
                $extra[] = $entry['approved'] ? __('resources/project/strings.workflow.log.approved') : __('resources/project/strings.workflow.log.rejected');
            }
            if (!empty($entry['forced'])) {
                $extra[] = __('resources/project/strings.workflow.forced');
            }
            if (!empty($entry['note'])) {
                $extra[] = e($entry['note']);
            }
            $extraText = $extra ? ' · ' . implode(' · ', $extra) : '';

            return '<li>' . e($stepLabel) . " — {$who} — {$when}{$extraText}</li>";
        })->implode('');

        return '<ol style="margin:0;padding-inline-start:20px;">' . $items . '</ol>';
    }
}
