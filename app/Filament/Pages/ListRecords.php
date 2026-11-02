<?php

namespace App\Filament\Pages;

use App\Filament\Traits\HandlesActionExceptions;
use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ListRecords extends BaseListRecords
{
    use HandlesActionExceptions;

    protected function makeBaseTable(): Table
    {
        $table = parent::makeBaseTable();

        return $table->hasCustomRecordUrl()
            ? $table
            : $table->recordUrl(fn (Model $record): ?string => $this->rowClickUrl($table, $record));
    }

    private function rowClickUrl(Table $table, Model $record): ?string
    {
        $resource = static::getResource();
        $editUrl = $resource::hasPage('edit') && $resource::canEdit($record)
            ? $resource::getUrl('edit', ['record' => $record])
            : null;

        if (auth()->user()?->getPreference('row_click_edit') && $editUrl) {
            return $editUrl;
        }

        $view = $table->getAction('view');

        if (! $view) {
            return $editUrl;
        }

        $view = (clone $view)->record($record);

        return $view->isHidden() ? $editUrl : $view->getUrl();
    }
}
