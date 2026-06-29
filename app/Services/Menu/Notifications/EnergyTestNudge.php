<?php

namespace App\Services\Menu\Notifications;

use App\Models\EnergyTest;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use Morilog\Jalali\Jalalian;

class EnergyTestNudge implements MenuNudge
{
    public function getKey(): string
    {
        return 'energy-controller:nudge';
    }

    public function triggers(): array
    {
        return [
            ['class' => User::class, 'on' => ['created', 'updated'], 'subject' => null],
            ['class' => EnergyTest::class, 'on' => ['created', 'deleted'], 'subject' => fn($test) => $test->user],
        ];
    }

    public function show($subject, User $user): bool
    {
        $now = Jalalian::now();

        if ($now->getDay() > 7) {
            return false;
        }

        $startOfMonth = (new Jalalian($now->getYear(), $now->getMonth(), 1))->toCarbon();

        return !EnergyTest::where('user_id', $user->id)
            ->where('completed_at', '>=', $startOfMonth)
            ->exists();
    }

    public function for($subject)
    {
        if ($subject instanceof User) {
            return User::active()->where('id', $subject->id)->get();
        }

        return collect();
    }

    public function title($subject, User $user): string
    {
        return 'ارزیابی انرژی ماهانه';
    }

    public function body($subject, User $user): string
    {
        return 'لطفاً پرسشنامه ارزیابی انرژی این ماه خود را تکمیل کنید.';
    }

    public function refresh(): bool
    {
        return true;
    }
}
