<?php

namespace App\Filament\Resources\NotifyRuleResource\Pages;

use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Schemas\Action\ViewSwitchActions;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyLogInfolistPresenter;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyLogTablePresenter;
use App\Models\NotifyLog;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ListNotifyLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = NotifyRuleResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return $action instanceof ViewAction ? $this->infolist(...) : null;
    }

    public function getTitle(): string|Htmlable
    {
        return __('resources/notify_rule/strings.log.heading');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->extraAttributes(['class' => 'fi-infolist-panel'])
                ->hiddenLabel()
                ->schema([
                    NotifyLogInfolistPresenter::status(),
                    NotifyLogInfolistPresenter::source(),
                    NotifyLogInfolistPresenter::rule(),
                    NotifyLogInfolistPresenter::event(),
                    NotifyLogInfolistPresenter::recordId(),
                    NotifyLogInfolistPresenter::createdAt(),

                    NotifyLogInfolistPresenter::targeted(),
                    NotifyLogInfolistPresenter::sent(),
                    NotifyLogInfolistPresenter::failed(),
                    NotifyLogInfolistPresenter::skipped(),
                    NotifyLogInfolistPresenter::runError(),
                    NotifyLogInfolistPresenter::problems(),

                    NotifyLogInfolistPresenter::sender(),
                    NotifyLogInfolistPresenter::recipient(),
                    NotifyLogInfolistPresenter::user(),
                    NotifyLogInfolistPresenter::channel(),
                    NotifyLogInfolistPresenter::count(),
                    NotifyLogInfolistPresenter::error(),
                ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => NotifyLog::query()->with('rule:id,name'))
            ->columns([
                NotifyLogTablePresenter::createdAt(),
                NotifyLogTablePresenter::source(),
                NotifyLogTablePresenter::rule(),
                NotifyLogTablePresenter::event(),
                NotifyLogTablePresenter::channel(),
                NotifyLogTablePresenter::status(),
                NotifyLogTablePresenter::count(),
                NotifyLogTablePresenter::recipient(),
                NotifyLogTablePresenter::user(),
                NotifyLogTablePresenter::error(),
                NotifyLogTablePresenter::run(),
            ])
            ->filters([
                NotifyLogTablePresenter::statusFilter(),
                NotifyLogTablePresenter::problemsFilter(),
                NotifyLogTablePresenter::sourceFilter(),
                NotifyLogTablePresenter::channelFilter(),
                NotifyLogTablePresenter::eventFilter(),
                NotifyLogTablePresenter::ruleFilter(),
                NotifyLogTablePresenter::userFilter(),
                NotifyLogTablePresenter::departmentFilter(),
                NotifyLogTablePresenter::dateRangeFilter(),
            ])
            ->filtersFormColumns(3)
            ->groups(NotifyLogTablePresenter::groups())
            ->recordActions([NotifyRuleResource::viewAction()->modalHeading(__('resources/notify_rule/strings.log.view_title'))], RecordActionsPosition::AfterCells)
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->paginationMode(PaginationMode::Simple)
            ->emptyStateIcon('heroicon-o-paper-airplane')
            ->emptyStateHeading(__('resources/notify_rule/strings.log.empty_heading'))
            ->emptyStateDescription(NotifyLogTablePresenter::emptyDescription())
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [...ViewSwitchActions::make('logs'), NotifyRuleResource::setupGuideAction()];
    }
}
