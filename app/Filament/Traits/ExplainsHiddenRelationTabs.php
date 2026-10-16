<?php

namespace App\Filament\Traits;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

trait ExplainsHiddenRelationTabs
{
    public function content(Schema $schema): Schema
    {
        $content = parent::content($schema);
        $notice = rescue(fn() => $this->hiddenRelationTabsNotice(), null);

        return $notice ? $content->components([$notice, ...$content->getComponents(withHidden: true)]) : $content;
    }

    protected function hiddenRelationTabsNotice(): ?Callout
    {
        if (!$this->hasRecord() || !Auth::check()) {
            return null;
        }

        $record = $this->getRecord();

        $tabs = collect($this->getAllRelationManagers())
            ->flatMap(fn($manager) => $manager instanceof RelationGroup ? $manager->getManagers() : [$manager])
            ->map(fn($manager) => $manager instanceof RelationManagerConfiguration ? $manager->relationManager : $manager)
            ->map(fn($manager) => rescue(fn() => ($module = $manager::deniedByPermission($record))
                ? __('resources/general/strings.hidden_tabs.tab', ['title' => (string)$manager::getTitle($record, static::class), 'module' => $module])
                : null, null))
            ->filter()
            ->values();

        if ($tabs->isEmpty()) {
            return null;
        }

        return Callout::make(__('resources/general/strings.hidden_tabs.' . ($tabs->count() > 1 ? 'many' : 'one'), ['tabs' => Arr::join($tabs->all(), '، ', ' و ')]))
            ->description(__('resources/general/strings.hidden_tabs.pointer'))
            ->info()
            ->icon('heroicon-o-lock-closed');
    }
}
