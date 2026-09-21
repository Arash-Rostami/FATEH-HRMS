<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Traits\FilamentActions;
use App\Traits\FilamentWorkflowPresenter;
use App\Filament\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
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
            ->modifyQueryUsing(fn(Builder $query) => $query->with('project:id,name'))
            ->columns([
                ...self::workflowColumns(),
                TextColumn::make('project.name')
                    ->label(__('resources/project/strings.label'))
                    ->placeholder('—')
                    ->searchable(),
                ...self::workflowTimestampColumns(),
            ])
            ->recordActions([
                self::viewAction(),
                self::cancelWorkflowAction(),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-bookmark')
            ->defaultSort('created_at', 'desc');
    }
}
