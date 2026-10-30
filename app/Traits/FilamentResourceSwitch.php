<?php

namespace App\Traits;

use Filament\Actions\Action;
use Illuminate\Support\Str;

trait FilamentResourceSwitch
{
    abstract protected static function switchGroup(): array;

    public static function switchActions(string $active): array
    {
        $resources = array_filter(static::switchGroup(), fn (string $resource) => $resource::canViewAny());

        return array_values(array_map(
            fn (string $resource) => Action::make('switch_'.Str::snake(class_basename($resource)))
                ->label($resource::getPluralModelLabel())
                ->icon($resource::getNavigationIcon())
                ->url($resource::getUrl())
                ->color($resource === $active ? 'primary' : 'gray')
                ->outlined($resource !== $active),
            $resources,
        ));
    }
}
