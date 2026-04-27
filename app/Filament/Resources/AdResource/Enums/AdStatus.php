<?php

namespace App\Filament\Resources\AdResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AdStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public static function fromBool(bool $active): self
    {
        return $active ? self::Active : self::Inactive;
    }

    public function getColor(): string
    {
        return match($this) {
            self::Active   => 'success',
            self::Inactive => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::Active   => 'heroicon-o-check-circle',
            self::Inactive => 'heroicon-o-x-circle',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::Active   => __('resources/ad/strings.status.active'),
            self::Inactive => __('resources/ad/strings.status.inactive'),
        };
    }
}
