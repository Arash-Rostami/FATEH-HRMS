<?php

namespace App\Services\Menu\Notifications;

use App\Models\DMS;
use App\Models\Read;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Illuminate\Support\Collection;

class DmsNudge implements MenuNudge
{
    private array $signedUserIds = [];

    public function body($subject, User $user): string
    {
        $typeLabel = $subject->type ? __('resources/dms/strings.type.systematic') : __('resources/dms/strings.type.non_systematic');

        if ($this->requiresSignFor($user)) {
            return 'سند ' . $typeLabel . ' «' . $subject->title . '» نیازمند تأیید/امضای شماست؛ برای مشاهده به مدیریت اسناد مراجعه کنید.';
        }

        return 'سند ' . $typeLabel . ' «' . $subject->title . '» را تأیید کرده‌اید اما هنوز مطالعه نکرده‌اید؛ به مدیریت اسناد مراجعه کنید.';
    }

    public function for($subject): Collection
    {
        $this->signedUserIds = array_fill_keys($subject->signedUserIds(), true);

        return $subject->pendingRecipients();
    }

    public function getKey(): string
    {
        return 'dms-controller:nudge';
    }

    public function badgeSuppressesCreate(): bool
    {
        return false;
    }

    public function refresh(): bool
    {
        return true;
    }

    public function show($subject, User $user): bool
    {
        return true;
    }

    public function title($subject, User $user): string
    {
        $typeLabel = $subject->type ? __('resources/dms/strings.type.systematic') : __('resources/dms/strings.type.non_systematic');

        return $this->requiresSignFor($user)
            ? 'سند ' . $typeLabel . ' نیازمند تأیید'
            : 'سند ' . $typeLabel . ' نیازمند مطالعه';
    }

    public function triggers(): array
    {
        return [
            ['class' => DMS::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
            ['class' => Read::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => fn(Read $r) => $r->dms],
        ];
    }

    public function url($subject): ?string
    {
        return route('dms', ['open' => $subject->getKey()]);
    }

    private function requiresSignFor(User $user): bool
    {
        return !isset($this->signedUserIds[$user->id]);
    }
}