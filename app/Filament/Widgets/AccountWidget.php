<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserResource\Enums\UserRole;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class AccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.account-widget';

    protected function getViewData(): array
    {
        $user = Auth::user();
        $role = $user?->role ? UserRole::tryFrom($user->role) : null;

        return [
            'user' => $user,
            'greeting' => shortGreeting(),
            'roleLabel' => $role?->getLabel(),
            'department' => $user?->profile?->department?->displayLabel(),
            'jalaliDate' => convertToPersian(Jalalian::now()->format('l | d F')),
        ];
    }
}
