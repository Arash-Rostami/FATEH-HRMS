<?php

namespace App\View\Composers;

use App\Models\User;
use Illuminate\View\View;

class OccasionComposer
{
    public function compose(View $view): void
    {
        [$type, $title, $message] = $this->occasion();

        $view->with([
            'occasionType' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function occasion(): array
    {
        $user = auth()->user();

        if (!$user || !function_exists('isSpecialDay')) {
            return [null, '', ''];
        }

        if (isSpecialDay('birthdate')) {
            return ['birthday', $this->birthdayTitle($user), $this->pick($this->birthdayQuotes())];
        }

        if (isSpecialDay('start_date')) {
            return ['anniversary', $this->anniversaryTitle($user), $this->pick($this->anniversaryQuotes())];
        }

        return [null, '', ''];
    }

    private function pick(array $quotes): string
    {
        return $quotes[array_rand($quotes)];
    }

    private function birthdayTitle(User $user): string
    {
        cache()->put('birthdate_' . $user->id, 'shown', now()->endOfDay());

        return 'تولدتان مبارک ' . ($user->name ?? 'عزیز') . '!';
    }

    private function anniversaryTitle(User $user): string
    {
        cache()->put('start_date_' . $user->id, 'shown', now()->endOfDay());

        return 'سالگرد همکاریتان مبارک ' . ($user->name ?? 'عزیز') . '!';
    }

    private function birthdayQuotes(): array
    {
        return [
            "امیدوارم روزت پر از خنده و شادی باشه.",
            "برات آرزوی سلامتی، شادی و موفقیت در سال پیش رو دارم.",
            "زندگی یک سفره و امروز شروع یک فصل جدیده.",
            "تولدت بهانه‌ای برای شروع دوباره است. از سفرت لذت ببر!",
            "جوانی هدیه طبیعته، اما پختگی یک اثر هنریه.",
            "یک سال بزرگتر، یک سال عاقل‌تر.",
            "امروز همه چیز درباره توست. از هر لحظه‌اش لذت ببر.",
            "تولدت مبارک! امیدوارم بهترین سال زندگیت رو پیش رو داشته باشی."
        ];
    }

    private function anniversaryQuotes(): array
    {
        return [
            "تبریک بابت رسیدن به یک نقطه عطف دیگر در مسیر شغلی‌تان!",
            "به امید سالی سرشار از رشد، یادگیری و موفقیت.",
            "تلاش و تعهد شما تأثیر بزرگی بر تیم ما داشته است.",
            "سپاس بابت تعهد و مشارکت‌های ارزشمندتان در شرکت.",
            "امیدواریم این سالگرد، شروعی برای موفقیت‌های بزرگ‌تر باشد.",
            "اشتیاق و تعهد شما همواره الهام‌بخش ماست.",
            "یک سال دیگر از همکاری گذشت. آینده شما در اینجا روشن است!",
            "ممنون که با مهارت و تعهدتان در کنار ما هستید."
        ];
    }
}