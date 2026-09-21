<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Livewire\Dashboard\Project\Actions\ApplyTemplateAction;
use App\Models\Workflow;
use App\Traits\FilamentActions;
use App\Traits\FilamentWorkflowPresenter;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Filament\RelationManagers\RelationManager;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkflowsRelationManager extends RelationManager
{
    use FilamentActions, FilamentWorkflowPresenter;

    protected static string $relationship = 'workflows';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->instances())
            ->columns([
                ...self::workflowColumns(),
                ...self::workflowTimestampColumns(),
            ])
            ->headerActions([
                Action::make('applyTemplate')
                    ->label(__('resources/project/strings.workflow.actions.apply_template'))
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Select::make('template_id')
                            ->label(__('resources/project/strings.workflow.fields.template'))
                            ->options(fn() => Workflow::templates()->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->searchable(),
                    ])
                    ->action(function (array $data) {
                        $template = Workflow::findOrFail($data['template_id']);
                        app(ApplyTemplateAction::class)->execute($template, $this->getOwnerRecord());

                        Notification::make()
                            ->title(__('resources/project/strings.workflow.actions.apply_template_success'))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                self::viewAction(),
                self::cancelWorkflowAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
