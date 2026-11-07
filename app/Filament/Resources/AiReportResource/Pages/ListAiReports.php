<?php

namespace App\Filament\Resources\AiReportResource\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\AiReportResource;
use App\Filament\Resources\ReportResource;
use App\Models\AiReport;
use App\Traits\FilamentHeaderActions;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAiReports extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = AiReportResource::class;

    protected function listHeaderActions(): array
    {
        return [...ReportResource::switchActions(static::$resource), AiReportResource::setupGuideAction()];
    }

    public function getTabs(): array
    {
        if (!(auth()->user()?->getPreference('show_list_tabs', true) ?? true)) {
            return [];
        }

        return [
            'all' => Tab::make(__('resources/ai_report/strings.tabs.all'))
                ->icon('heroicon-o-list-bullet'),

            'generating' => Tab::make(__('resources/ai_report/strings.tabs.generating'))
                ->icon('heroicon-o-arrow-path')
                ->badge(fn() => $this->getStats()->generating_count ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', AiReport::STATUS_GENERATING)),

            'ready' => Tab::make(__('resources/ai_report/strings.tabs.ready'))
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => $this->getStats()->ready_count ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', AiReport::STATUS_READY)),

            'failed' => Tab::make(__('resources/ai_report/strings.tabs.failed'))
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => $this->getStats()->failed_count ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', AiReport::STATUS_FAILED)),

            'drafts' => Tab::make(__('resources/ai_report/strings.tabs.drafts'))
                ->icon('heroicon-o-document')
                ->badge(fn() => $this->getStats()->draft_count ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('saved_at')->where('status', '!=', AiReport::STATUS_GENERATING)),
        ];
    }

    private function getStats(): object
    {
        return once(fn() => AiReport::query()
            ->selectRaw("
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS generating_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS ready_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS failed_count,
                SUM(CASE WHEN saved_at IS NULL AND status != ? THEN 1 ELSE 0 END) AS draft_count
            ", [AiReport::STATUS_GENERATING, AiReport::STATUS_READY, AiReport::STATUS_FAILED, AiReport::STATUS_GENERATING])
            ->first());
    }
}
