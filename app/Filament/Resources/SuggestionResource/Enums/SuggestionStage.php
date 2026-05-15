<?php

namespace App\Filament\Resources\SuggestionResource\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SuggestionStage: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case TeamRemarks = 'team_remarks';
    case DeptRemarks = 'dept_remarks';
    case AwaitingDecision = 'awaiting_decision';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case UnderReview = 'under_review';
    case Closed = 'closed';

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::TeamRemarks => 'info',
            self::DeptRemarks => 'info',
            self::AwaitingDecision => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::UnderReview => 'warning',
            self::Closed => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::TeamRemarks => 'heroicon-o-chat-bubble-left-right',
            self::DeptRemarks => 'heroicon-o-building-office-2',
            self::AwaitingDecision => 'heroicon-o-scale',
            self::Accepted => 'heroicon-o-check-circle',
            self::Rejected => 'heroicon-o-x-circle',
            self::UnderReview => 'heroicon-o-magnifying-glass',
            self::Closed => 'heroicon-o-lock-closed',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('resources/suggestion/strings.tabs.pending'),
            self::TeamRemarks => __('resources/suggestion/strings.tabs.team_remarks'),
            self::DeptRemarks => __('resources/suggestion/strings.tabs.dept_remarks'),
            self::AwaitingDecision => __('resources/suggestion/strings.tabs.awaiting_decision'),
            self::Accepted => __('resources/suggestion/strings.tabs.accepted'),
            self::Rejected => __('resources/suggestion/strings.tabs.rejected'),
            self::UnderReview => __('resources/suggestion/strings.tabs.under_review'),
            self::Closed => __('resources/suggestion/strings.tabs.closed'),
        };
    }
}
