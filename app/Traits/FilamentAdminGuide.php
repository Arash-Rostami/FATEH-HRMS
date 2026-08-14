<?php

namespace App\Traits;

use Filament\Actions\Action;

trait FilamentAdminGuide
{
    public static function guideTabs(): array
    {
        return property_exists(static::class, 'guide') ? static::$guide : [];
    }

    public static function setupGuideAction(): Action
    {
        return Action::make('moduleGuide')
            ->label(__('resources/general/strings.guide.label'))
            ->icon('heroicon-o-book-open')
            ->modalHeading(static::getPluralModelLabel() . ' — ' . __('resources/general/strings.guide.heading'))
            ->modalContent(fn() => view('filament.components.admin-guide', ['tabs' => static::guideTabs()]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('resources/general/strings.guide.cancel'));
    }

    public static function guideEmptyStateActions(): array
    {
        return [static::setupGuideAction()];
    }
}