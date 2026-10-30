<?php

namespace App\Traits;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use App\Filament\Resources\NotifyRuleResource\Pages\ListNotifyLogs;
use App\Filament\Resources\SkillRequestResource\Pages\ListSkillRequests;
use Filament\Tables\Table;

trait FilamentViewActionDefaults
{
    public static function viewModalShortcuts(?Table $table): array
    {
        if ($table === null) {
            return [];
        }

        $shortcuts = [];

        if ($table->getRelationship() !== null) {
            foreach ($table->getHeaderActions() as $headerAction) {
                if ($headerAction->getName() === 'create') {
                    $shortcuts[] = $headerAction->getClone()->button();
                }
            }
        } elseif (! in_array($table->getLivewire()::class, [ListNotifyLogs::class, ListSkillRequests::class], true)) {
            $shortcuts[] = CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label(__('resources/general/strings.table.action_create'));
        }

        foreach ($table->getRecordActions() as $action) {
            $actions = $action instanceof ActionGroup ? $action->getFlatActions() : [$action];

            foreach ($actions as $rowAction) {
                if (! in_array($rowAction->getName(), ['edit', 'delete'], true)) {
                    continue;
                }

                $shortcuts[] = $rowAction->getClone()
                    ->button()
                    ->label(__('resources/general/strings.table.action_' . $rowAction->getName()))
                    ->cancelParentActions($rowAction->getName() === 'delete' ? true : null);
            }
        }

        return $shortcuts;
    }

    private static function quickActionIcons(?Action $action): string
    {
        if (! $action instanceof ViewAction) {
            return '';
        }

        $icons = collect($action->getExtraModalFooterActions())
            ->filter(fn ($footerAction) => $footerAction->isVisible())
            ->reverse()
            ->map(fn ($footerAction) => (clone $footerAction)->iconButton()->toHtml())
            ->implode('');

        return $icons === '' ? '' : '<div wire:key="view-modal-quick-actions" class="fi-modal-header-quick-actions">' . $icons . '</div>';
    }
}