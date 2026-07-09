<?php

namespace App\Services\Menu\Notifications;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Facades\DB;

class ChannelInviteNudge implements MenuNudge
{
    public function body($subject, User $user): string
    {
        return 'شما به کانال «' . $subject->name . '» دعوت شده‌اید. برای ورود روی آن کلیک کنید.';
    }

    public function for($subject)
    {
        $userIds = DB::table('channel_members')
            ->where('channel_id', $subject->id)
            ->whereNull('entered_at')
            ->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }

    public function getKey(): string
    {
        return 'channels-controller:nudge';
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return ChannelMember::query()
            ->where('channel_id', $subject->id)
            ->where('user_id', $user->id)
            ->whereNull('entered_at')
            ->exists();
    }

    public function title($subject, User $user): string
    {
        return 'دعوت به کانال: ' . $subject->name;
    }

    public function triggers(): array
    {
        return [
            ['class' => Channel::class, 'on' => ['deleted', 'forceDeleted'], 'subject' => null],
        ];
    }
}