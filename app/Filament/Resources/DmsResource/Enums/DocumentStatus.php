<?php

namespace App\Filament\Resources\DmsResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Live = 'live';
    case UnderReview = 'under_review';
    case Obsolete = 'obsolete';

    public function getColor(): string
    {
        return match ($this) {
            self::Live => 'success',
            self::UnderReview => 'warning',
            self::Obsolete => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Live => 'heroicon-o-check-circle',
            self::UnderReview => 'heroicon-o-clock',
            self::Obsolete => 'heroicon-o-x-circle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Live => __('resources/dms/strings.enums.status.live'),
            self::UnderReview => __('resources/dms/strings.enums.status.under_review'),
            self::Obsolete => __('resources/dms/strings.enums.status.obsolete'),
        };
    }
}
