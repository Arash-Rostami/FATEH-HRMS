<?php

namespace App\Filament\Resources\SkillRequestResource\Pages;

use App\Enums\SkillRequestStatus;
use App\Filament\Resources\SkillRequestResource;
use App\Models\SkillUser;
use App\Traits\FilamentHeaderActions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSkillRequests extends ListRecords
{
    use FilamentHeaderActions;

    protected static string $resource = SkillRequestResource::class;

    protected function listHeaderActions(): array
    {
        return [SkillRequestResource::setupGuideAction()];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('همه')->icon('heroicon-o-list-bullet'),
        ] + collect(SkillRequestStatus::cases())->mapWithKeys(fn(SkillRequestStatus $status) => [
            $status->value => Tab::make($status->label())
                ->icon($status->heroicon())
                ->badge(fn() => $this->getStats()->{$status->value} ?: null)
                ->badgeColor($status->color())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', $status)),
        ])->all();
    }

    private function getStats(): object
    {
        return once(fn() => SkillUser::query()
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected")
            ->first());
    }
}
