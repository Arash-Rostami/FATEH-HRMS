<?php

namespace App\Traits;

use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\HtmlString;

trait FilamentFormDivider
{
    public static function divider(): TextEntry
    {
        return TextEntry::make('divider')
            ->hiddenLabel()
            ->columnSpanFull()
            ->state(new HtmlString(
                '<div class="w-2/3 h-px bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-700 to-transparent opacity-80 mx-auto"></div>'
            ));
    }
}
