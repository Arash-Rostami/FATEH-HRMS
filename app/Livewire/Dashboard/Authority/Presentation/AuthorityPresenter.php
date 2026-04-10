<?php

namespace App\Livewire\Dashboard\Authority\Presentation;

use App\Models\Authority;

class AuthorityPresenter
{
    public function delegationBadge(?string $v): array
    {
        return [
            'label' => $this->delegationLevel($v),
            'class' => match ($v) {
                'decision_implementation' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                'review_proposal' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
                'review_reporting' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                'decision_reporting' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
                default => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]',
            },
        ];
    }

    public function delegationLevel(?string $v): string
    {
        return match ($v) {
            'decision_implementation' => 'تصمیم گیری و اجرا',
            'review_proposal' => 'بررسی و پیشنهاد',
            'review_reporting' => 'بررسی و گزارش',
            'decision_reporting' => 'تصمیم گیری و گزارش',
            default => $v ?? 'داده نشده!',
        };
    }

    public function managerRows(array $details): array
    {
        return [
            [
                'label' => 'روش اجرایی',
                'icon' => match ($details['execution_procedure'] ?? null) {
                    'yes' => 'check_circle',
                    'no' => 'cancel',
                    'pending' => 'pending',
                    default => 'help_outline',
                },
                'value' => match ($details['execution_procedure'] ?? null) {
                    'yes' => 'دارد',
                    'no' => 'ندارد',
                    'pending' => 'در انتظار (تصویب / بروزرسانی)',
                    default => 'داده نشده!',
                },
                'color' => match ($details['execution_procedure'] ?? null) {
                    'yes' => 'text-[var(--md-sys-color-primary)]',
                    'no' => 'text-[var(--md-sys-color-error)]',
                    'pending' => 'text-[var(--md-sys-color-tertiary)]',
                    default => 'text-[var(--md-sys-color-on-surface-variant)]',
                },
            ],
            [
                'label' => 'فراوانی تکرار',
                'icon' => match ($details['repeat_frequency'] ?? null) {
                    'yearly' => 'event_repeat',
                    'biyearly' => 'calendar_view_month',
                    'quarterly' => 'date_range',
                    '5_times_a_year' => 'filter_5',
                    'frequent' => 'repeat_on',
                    'regular' => 'schedule',
                    'occasional' => 'event_available',
                    default => 'help_outline',
                },
                'value' => match ($details['repeat_frequency'] ?? null) {
                    'yearly' => 'یک بار در سال',
                    'biyearly' => 'دو بار در سال',
                    'quarterly' => 'فصلی',
                    '5_times_a_year' => 'پنج بار در سال',
                    'frequent' => 'بیشتر از 4  بار در ماه',
                    'regular' => 'بین 1 تا 4 بار در ماه',
                    'occasional' => 'کمتر از  بار در ماه',
                    default => 'داده نشده!',
                },
                'color' => match ($details['repeat_frequency'] ?? null) {
                    'frequent' => 'text-[var(--md-sys-color-error)]',
                    'regular' => 'text-[var(--md-sys-color-primary)]',
                    'yearly', 'biyearly', 'quarterly' => 'text-[var(--md-sys-color-tertiary)]',
                    default => 'text-[var(--md-sys-color-on-surface-variant)]',
                },
            ],
            [
                'label' => 'شاخص اثر',
                'icon' => match ($details['impact_score'] ?? null) {
                    'very_high' => 'trending_up',
                    'high' => 'show_chart',
                    'medium' => 'equalizer',
                    'low' => 'trending_down',
                    default => 'bar_chart',
                },
                'value' => match ($details['impact_score'] ?? null) {
                    'very_high' => 'خیلی زیاد',
                    'high' => 'زیاد',
                    'medium' => 'متوسط',
                    'low' => 'کم',
                    default => 'داده نشده!',
                },
                'color' => match ($details['impact_score'] ?? null) {
                    'very_high' => 'text-[var(--md-sys-color-error)]',
                    'high' => 'text-[var(--md-sys-color-primary)]',
                    'medium' => 'text-[var(--md-sys-color-tertiary)]',
                    default => 'text-[var(--md-sys-color-on-surface-variant)]',
                },
            ],
            [
                'label' => 'تفویض پیشنهادی',
                'icon' => match ($details['proposed_delegation'] ?? null) {
                    'decision_implementation' => 'verified',
                    'review_proposal' => 'rate_review',
                    'review_reporting' => 'assignment',
                    'decision_reporting' => 'fact_check',
                    default => 'edit_note',
                },
                'value' => $this->delegationLevel($details['proposed_delegation'] ?? null),
                'color' => match ($details['proposed_delegation'] ?? null) {
                    'decision_implementation' => 'text-[var(--md-sys-color-primary)]',
                    'review_proposal' => 'text-[var(--md-sys-color-tertiary)]',
                    'review_reporting' => 'text-[var(--md-sys-color-secondary)]',
                    'decision_reporting' => 'text-[var(--md-sys-color-error)]',
                    default => 'text-[var(--md-sys-color-on-surface-variant)]',
                },
            ],
        ];
    }

    public function position(?string $v): string
    {
        return match ($v) {
            'c-manager' => 'مدیر ارشد',
            'manager' => 'مدیر',
            'supervisor' => 'سرپرست',
            'senior' => 'ارشد',
            'expert' => 'کارشناس',
            'employee' => 'کارمند',
            default => $v ?? '—',
        };
    }

    public function responsibleIcon(Authority $authority): string
    {
        return $authority->sub_duty ? 'groups' : 'person';
    }

    public function responsibleLabel(Authority $authority): string
    {
        if ($authority->sub_duty) return 'وظایف زیرمجموعه';
        return $authority->user?->name ?? 'بدون مسئول';
    }
}
