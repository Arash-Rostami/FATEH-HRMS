<?php

namespace App\Livewire\Dashboard\Reminder\Presentation;

use App\Models\DMS;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\Ticket;
use App\Traits\RiskEscalationChip;

class ReminderPresenter
{
    use RiskEscalationChip;

    public function __construct(
        protected Reminder $reminder
    ) {
    }

    public static function filterOptions(): array
    {
        return [
            'active' => 'همه فعال',
            'today' => 'امروز',
            'overdue' => 'دیرکرد',
            'week' => 'این هفته',
            'snoozed' => 'اعلان خاموش',
            'completed' => 'انجام‌شده',
        ];
    }

    public static function columns(): array
    {
        return [
            ['label' => 'عنوان', 'align' => 'text-right'],
            ['label' => 'سررسید', 'align' => 'text-center'],
            ['label' => 'تکرار', 'align' => 'text-center'],
            ['label' => 'کانال‌ها', 'align' => 'text-center'],
            ['label' => 'اقدامات', 'align' => 'text-center'],
        ];
    }

    public static function snoozePresets(): array
    {
        return [
            '1h' => '۱ ساعت دیگر',
            'tonight' => 'امشب',
            'tomorrow' => 'فردا',
            'nextweek' => 'هفته بعد',
        ];
    }

    public static function channelToggles(): array
    {
        return [
            ['name' => 'form.emailEnabled', 'label' => 'ایمیل'],
            ['name' => null, 'label' => 'پیامک (به‌زودی)', 'disabled' => true],
            ['name' => 'form.inAppBadge', 'label' => 'نشانگر منو'],
            ['name' => 'form.inAppNudge', 'label' => 'پیام اعلانات'],
            ['name' => 'form.inAppEdge', 'label' => 'پیام ثابت صفحه'],
        ];
    }

    public function channelBadges(): array
    {
        $channels = $this->reminder->channels ?? [];
        $badges = [];

        if (in_array('email', $channels, true)) {
            $badges[] = ['icon' => 'mail', 'label' => 'ایمیل'];
        }

        foreach ([
                     'badge' => ['badge', 'نشانگر منو'],
                     'nudge' => ['campaign', 'پیام اعلانات'],
                     'edge' => ['push_pin', 'پیام ثابت صفحه'],
                 ] as $signal => [$icon, $label]) {
            if (Reminder::channelEnabled($channels, $signal)) {
                $badges[] = ['icon' => $icon, 'label' => $label];
            }
        }

        return $badges;
    }

    public function hostIcon(): string
    {
        return static::hostIconFor($this->reminder->remindable_type);
    }

    public static function hostIconFor(?string $remindableType): string
    {
        return match ($remindableType) {
            Task::class => 'dashboard',
            Ticket::class => 'support_agent',
            Project::class => 'workspaces',
            DMS::class => 'description',
            Reservation::class => 'event',
            default => 'public',
        };
    }

    public function hostLabel(): string
    {
        $type = $this->reminder->remindable_type;

        if ($type === null) {
            return 'عمومی';
        }

        $label = match ($type) {
            Task::class => 'وظیفه',
            Ticket::class => 'تیکت',
            Project::class => 'پروژه',
            DMS::class => 'سند',
            Reservation::class => 'رزرو',
            default => 'رکورد',
        };

        return $label . ' #' . convertToPersian((string) $this->reminder->remindable_id);
    }

    public function statusTimingLabel(): ?string
    {
        if ($completedAt = $this->reminder->completed_at) {
            return 'در ' . toJalali($completedAt, 'j F، H:i');
        }

        $snoozedUntil = $this->reminder->snoozed_until;

        if ($snoozedUntil && $snoozedUntil->isFuture()) {
            return 'اعلان خاموش تا ' . toJalali($snoozedUntil, 'j F، H:i');
        }

        return null;
    }

    public function dueLabel(): string
    {
        if ($this->reminder->completed_at) {
            return 'انجام شد';
        }

        $due = $this->reminder->due_at;

        if ($due->isPast()) {
            return $due->isToday()
                ? 'دیرکرد امروز، ' . $due->format('H:i')
                : 'دیرکرد ' . convertToPersian((int) floor($due->diffInDays())) . ' روز';
        }

        if ($due->isToday()) {
            return 'امروز، ' . $due->format('H:i');
        }

        if ($due->isTomorrow()) {
            return 'فردا، ' . $due->format('H:i');
        }

        if ($due->diffInDays() <= 7) {
            return toJalali($due, 'l j F');
        }

        return toJalali($due, 'j F Y');
    }

    public function toneClasses(): string
    {
        return $this->riskToneClasses($this->tone());
    }

    public function statusIcon(): string
    {
        if ($this->reminder->completed_at) {
            return 'check_circle';
        }

        $due = $this->reminder->due_at;

        if ($due->isPast()) {
            return 'error';
        }

        if ($due->isToday()) {
            return 'today';
        }

        return 'event';
    }

    public function recurLabel(): string
    {
        return $this->reminder->recurs->label();
    }

    private function tone(): string
    {
        if ($this->reminder->completed_at) {
            return 'success';
        }

        $due = $this->reminder->due_at;

        if ($due->isPast()) {
            return 'error';
        }

        if ($due->isToday()) {
            return 'warning';
        }

        return 'success';
    }
}
