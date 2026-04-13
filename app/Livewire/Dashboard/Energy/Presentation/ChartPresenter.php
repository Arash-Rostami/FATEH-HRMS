<?php

namespace App\Livewire\Dashboard\Energy\Presentation;

class ChartPresenter extends BaseEnergyPresenter
{
    public function getDimensions(): array
    {
        return ['physique', 'emotion', 'mind', 'soul'];
    }

    public function getOverallMessage(int $score): string
    {
        return match (true) {
            $score <= 2  => 'تعادل عالی — همین روند رو ادامه بده 😍',
            $score <= 5  => 'وضعیت خوب — جای بهبود وجود داره 🙂',
            $score <= 9  => 'فرصت بازبینی — استراحت رو جدی بگیر 😌',
            $score <= 13 => 'نشانه‌های افت انرژی — برنامه‌ریزی لازمه 😕',
            default     => 'اولویت اول: سلامت جسم و ذهن 😔',
        };
    }

    public function getScoreColorVar(int $score): string
    {
        return match (true) {
            $score <= 1 => '--md-sys-color-secondary',
            $score <= 2 => '--md-sys-color-tertiary',
            default    => '--md-sys-color-error',
        };
    }

    public function getScoreLabel(int $score): string
    {
        $labels = [
            0 => 'عالی 😍',
            1 => 'خوب 🙂',
            2 => 'متوسط 😌',
            3 => 'نیاز به بهبود 😕',
            4 => 'نیاز جدی 😔',
        ];

        return $labels[min($score, 4)] ?? '';
    }
}
