<?php

namespace App\Filament\Resources\AuthorityResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DelegationLevel: string implements HasColor, HasIcon, HasLabel
{
    case DecisionImplementation = 'decision_implementation';
    case ReviewProposal         = 'review_proposal';
    case ReviewReporting        = 'review_reporting';
    case DecisionReporting      = 'decision_reporting';

    public function getLabel(): string
    {
        return match ($this) {
            self::DecisionImplementation => 'تصمیم گیری و اجرا',
            self::ReviewProposal         => 'بررسی و پیشنهاد',
            self::ReviewReporting        => 'بررسی و گزارش',
            self::DecisionReporting      => 'تصمیم گیری و گزارش',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DecisionImplementation => 'primary',
            self::ReviewProposal         => 'info',
            self::ReviewReporting        => 'warning',
            self::DecisionReporting      => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DecisionImplementation => 'heroicon-o-check-badge',
            self::ReviewProposal         => 'heroicon-o-document-magnifying-glass',
            self::ReviewReporting        => 'heroicon-o-clipboard-document',
            self::DecisionReporting      => 'heroicon-o-clipboard-document-check',
        };
    }
}
