<?php

namespace App\Filament\Resources\AdResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AdGender: string implements HasColor, HasIcon, HasLabel
{
    case Male   = 'Male';
    case Female = 'Female';
    case Any    = 'Any';

    public function getColor(): string
    {
        return match($this) {
            self::Male   => 'info',
            self::Female => 'pink',
            self::Any    => 'success',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::Male   => 'heroicon-o-user',
            self::Female => 'heroicon-o-user',
            self::Any    => 'heroicon-o-users',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::Male   => __('resources/ad/strings.gender.male'),
            self::Female => __('resources/ad/strings.gender.female'),
            self::Any    => __('resources/ad/strings.gender.any'),
        };
    }
}
