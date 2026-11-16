<?php

namespace App\Livewire\Dashboard\Launchpad\Presentation;

use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;

class LaunchpadPresenter
{
    private ?array $departments = null;

    public function listRow(Launchpad $row, User $user): array
    {
        $status = $row->status();
        $label = match ($status) {
            'inactive' => 'غیرفعال',
            'expired' => 'منقضی',
            'scheduled' => 'زمان‌بندی‌شده',
            'soon' => convertToPersian((string) max(0, (int) today()->diffInDays($row->expire_at, false))) . ' روز باقی‌مانده',
            default => 'فعال',
        };
        $this->departments ??= Department::getCachedOptions()->all();
        $block = LaunchpadAccessPolicy::workspaceBlock($row, $user);
        return [
            'id' => (int) $row->id,
            'title' => $row->title,
            'department' => $this->departments[$row->department_id] ?? $row->department_id,
            'owner' => $row->user?->name ?? '—',
            'audience' => convertToPersian((string) count(is_array($row->audience) ? $row->audience : [])),
            'status' => $label,
            'variant' => match ($status) {
                'live' => 'success',
                'soon' => 'warning',
                'expired' => 'danger',
                'scheduled' => 'info',
                default => 'neutral',
            },
            'can_manage' => LaunchpadAccessPolicy::canManage($row, $user),
            'block' => $block,
            'workspace_title' => match ($block) {
                null => 'افزودن به فضای کاری',
                'inactive' => 'برنامه غیرفعال است',
                'scheduled' => 'برنامه هنوز آغاز نشده است',
                'expired' => 'برنامه منقضی شده است',
                default => 'برنامه در دسترس شما نیست',
            },
        ];
    }

    public function capMeter(string $dept): array
    {
        $used = Launchpad::query()->where('department_id', $dept)->count();
        $cap = (int) config('launchpad.max_per_department');
        return ['used' => $used, 'cap' => $cap, 'pct' => $cap > 0 ? min(100, (int) round($used * 100 / $cap)) : 0, 'tone' => $this->tone($cap > 0 && $used >= $cap)];
    }

    public function precheck(array $report): array
    {
        $reasons = ['name' => 'نام نامعتبر', 'hidden' => 'فایل پنهان', 'executable' => 'فایل اجرایی مجاز نیست', 'extension' => 'پسوند مجاز نیست', 'size' => 'حجم بیش از حد', 'duplicate' => 'نام تکراری', 'content' => 'محتوای غیرمجاز'];
        $report['rejected'] = array_map(fn ($item) => ['name' => mb_substr((string) $item['name'], 0, 80), 'reason' => $reasons[$item['reason']] ?? 'نامعتبر'], array_slice($report['rejected'], 0, 20));
        $messages = ['empty' => 'فایلی انتخاب نشده است.', 'too_many' => 'تعداد فایل‌ها بیش از حد مجاز است.', 'too_big' => 'مجموع حجم فایل‌ها بیش از حد مجاز است.', 'no_entry' => 'فایل index.html در مجموعه نیست.'];
        $report['messages'] = array_values(array_map(fn ($key) => $messages[$key] ?? 'مجموعهٔ فایل‌ها نامعتبر است.', $report['errors']));
        $report['pct'] =$report['max_bytes'] > 0 ? min(100, (int) round($report['bytes'] * 100 / $report['max_bytes'])) : 0;
        $report['tone'] = $this->tone(!$report['ok']);
        return $report;
    }

    private function tone(bool $warning): array
    {
        return $warning
            ? ['text' => 'text-[var(--md-sys-color-error)]', 'fill' => 'bg-[var(--md-sys-color-error)]']
            : ['text' => 'text-[var(--md-sys-color-primary)]', 'fill' => 'bg-[var(--md-sys-color-primary)]'];
    }
}
