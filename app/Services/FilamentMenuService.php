<?php

namespace App\Services;

use App\Livewire\Admin\ManagePreferences;
use Filament\Actions\Action;

final class FilamentMenuService
{
    public static function getActions(): array
    {
        return [
//            self::group('ابزار'),
//            self::share(),
//            self::printPage(),
//            ...self::fullscreen(),
//            self::pictureInPicture(),
//            ...self::wakeLock(),
//            self::clearStorage(),
//            self::shortcuts(),
//            self::support(),

            self::group('پیمایش'),
            self::dashboard(),
            self::preferences(),
        ];
    }

    public static function clearStorage(): Action
    {
        return Action::make('clear_storage')
            ->label('پاک‌سازی کش')
            ->icon('heroicon-o-bolt')
            ->extraAttributes([
                'type' => 'button',
                'x-on:click.prevent' => 'window.filamentMenu.clearStorage()',
            ]);
    }

    public static function dashboard(): Action
    {
        return Action::make('dashboard')
            ->label('پنل کاربر')
            ->icon('heroicon-o-squares-2x2')
            ->url(fn () => route('dashboard'), shouldOpenInNewTab: true);
    }

    public static function pictureInPicture(): Action
    {
        return Action::make('pip')
            ->label('پنجره شناور')
            ->icon('heroicon-o-rectangle-group')
            ->extraAttributes([
                'type' => 'button',
                'x-on:click.prevent' => 'window.filamentMenu.requestPiP()',
            ]);
    }

    public static function preferences(): Action
    {
        return Action::make('preferences')
            ->label('تنظیمات')
            ->icon('heroicon-o-adjustments-horizontal')
            ->url(fn (): string => ManagePreferences::getUrl());
    }

    public static function printPage(): Action
    {
        return Action::make('print')
            ->label('چاپ صفحه')
            ->icon('heroicon-o-printer')
            ->extraAttributes([
                'type' => 'button',
                'x-on:click.prevent' => 'window.filamentMenu.printPage()',
            ]);
    }

    public static function share(): Action
    {
        return Action::make('share')
            ->label('اشتراک‌گذاری')
            ->icon('heroicon-o-share')
            ->extraAttributes([
                'type' => 'button',
                'x-on:click.prevent.stop' => 'window.filamentMenu.share()',
            ]);
    }

    public static function shortcuts(): Action
    {
        return Action::make('shortcuts')
            ->label('میانبرها')
            ->icon('heroicon-o-command-line')
            ->extraAttributes([
                'type' => 'button',
                'x-on:click.prevent' => 'window.filamentMenu.showShortcuts()',
            ]);
    }

    public static function support(): Action
    {
        return Action::make('support')
            ->label('پشتیبانی')
            ->icon('heroicon-o-lifebuoy')
            ->url(fn () => 'mailto:' . config('mail.from.address'));
    }

    public static function fullscreen(): array
    {
        return [
            Action::make('fullscreen_on')
                ->label('تمام صفحه')
                ->icon('heroicon-o-arrows-pointing-out')
                ->extraAttributes([
                    'type' => 'button',
                    'x-show' => '!$store.filamentMenu.fullscreen',
                    'x-on:click.prevent' => '$store.filamentMenu.toggleFullscreen()',
                ]),
            Action::make('fullscreen_off')
                ->label('خروج از حالت تمام صفحه')
                ->icon('heroicon-o-arrows-pointing-in')
                ->extraAttributes([
                    'type' => 'button',
                    'x-show' => '$store.filamentMenu.fullscreen',
                    'x-on:click.prevent' => '$store.filamentMenu.toggleFullscreen()',
                ]),
        ];
    }

    public static function wakeLock(): array
    {
        return [
            Action::make('wake_on')
                ->label('روشن نگه‌داشتن صفحه')
                ->icon('heroicon-o-sun')
                ->extraAttributes([
                    'type' => 'button',
                    'x-show' => '!$store.filamentMenu.wakeLock',
                    'x-on:click.prevent' => '$store.filamentMenu.toggleWakeLock()',
                ]),
            Action::make('wake_off')
                ->label('خاموش کردن قفل صفحه')
                ->icon('heroicon-o-moon')
                ->extraAttributes([
                    'type' => 'button',
                    'x-show' => '$store.filamentMenu.wakeLock',
                    'x-on:click.prevent' => '$store.filamentMenu.toggleWakeLock()',
                ]),
        ];
    }

    private static function group(string $label): Action
    {
        return Action::make('grp_' . md5($label))
            ->label($label)
            ->disabled()
            ->extraAttributes(['class' => 'pointer-events-none select-none mt-2 pb-1 opacity-50 text-[0.6875rem] font-semibold tracking-[0.05em]']);
    }
}
