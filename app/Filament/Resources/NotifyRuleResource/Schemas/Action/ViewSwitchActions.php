<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas\Action;

use App\Filament\Resources\NotifyRuleResource;
use Filament\Actions\Action;

class ViewSwitchActions
{
    private const VIEWS = [
        'rules' => ['icon' => 'heroicon-o-at-symbol', 'page' => 'index'],
        'logs' => ['icon' => 'paper-airplane', 'page' => 'logs'],
    ];

    public static function make(string $active): array
    {
        return array_map(
            fn (string $view, array $meta) => Action::make("switch_{$view}")
                ->label(__("resources/notify_rule/strings.log.switch_{$view}"))
                ->icon($meta['icon'] === 'paper-airplane' ? svg('heroicon-o-paper-airplane', 'size-5 rtl:-scale-x-100') : $meta['icon'])
                ->url(NotifyRuleResource::getUrl($meta['page']))
                ->color($view === $active ? 'primary' : 'gray')
                ->outlined($view !== $active),
            array_keys(self::VIEWS),
            self::VIEWS,
        );
    }
}
