<?php

namespace App\Services;

use App\Livewire\Admin\ManagePreferences;
use Filament\Actions\Action;

final class FilamentMenuService
{
    public static function getActions(): array
    {
        return [
            self::group('پیمایش'),
            self::dashboard(),
            self::preferences(),
        ];
    }

    public static function dashboard(): Action
    {
        return Action::make('dashboard')
            ->label('پنل کاربر')
            ->icon('heroicon-o-squares-2x2')
            ->url(fn () => route('dashboard'), shouldOpenInNewTab: true);
    }

    public static function preferences(): Action
    {
        return Action::make('preferences')
            ->label('تنظیمات')
            ->icon('heroicon-o-adjustments-horizontal')
            ->url(fn (): string => ManagePreferences::getUrl());
    }

    private static function group(string $label): Action
    {
        return Action::make('grp_' . md5($label))
            ->label($label)
            ->disabled()
            ->extraAttributes(['class' => 'pointer-events-none select-none mt-2 pb-1 opacity-50 text-[0.6875rem] font-semibold tracking-[0.05em]']);
    }
}
