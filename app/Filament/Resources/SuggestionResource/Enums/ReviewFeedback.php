<?php

namespace App\Filament\Resources\SuggestionResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReviewFeedback: string implements HasColor, HasIcon, HasLabel
{
    case Agree = 'agree';
    case Neutral = 'neutral';
    case Disagree = 'disagree';
    case Incomplete = 'incomplete';
    case Unknown = 'unknown';

    public function getColor(): string
    {
        return match ($this) {
            self::Agree => 'success',
            self::Neutral => 'gray',
            self::Disagree => 'danger',
            self::Incomplete => 'warning',
            self::Unknown => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Agree => 'heroicon-o-hand-thumb-up',
            self::Neutral => 'heroicon-o-minus-circle',
            self::Disagree => 'heroicon-o-hand-thumb-down',
            self::Incomplete => 'heroicon-o-exclamation-triangle',
            self::Unknown => 'heroicon-o-question-mark-circle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Agree => __('resources/suggestion/strings.feedback.agree'),
            self::Neutral => __('resources/suggestion/strings.feedback.neutral'),
            self::Disagree => __('resources/suggestion/strings.feedback.disagree'),
            self::Incomplete => __('resources/suggestion/strings.feedback.incomplete'),
            self::Unknown => __('resources/suggestion/strings.feedback.unknown'),
        };
    }
}
