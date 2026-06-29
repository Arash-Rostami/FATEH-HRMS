<?php

namespace App\Services\Menu\Indicators;

use App\Models\Suggestion;
use App\Services\Menu\Contracts\MenuBadge;

class PendingSuggestions implements MenuBadge
{
    public function getBody(): string
    {
        return 'در بخش پیشنهادها موردی نیازمند تصمیم یا پیگیری وجود دارد که باعث نمایش نشان هشدار در منو شده است.';
    }

    public function getKey(): string
    {
        return 'suggestion-controller';
    }

    public function getTitle(): string
    {
        return 'پیشنهادها نیاز به اقدام دارند';
    }

    public function isActive(): bool
    {
        return Suggestion::attentionRequired()->exists();
    }
}