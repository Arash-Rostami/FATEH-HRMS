<?php

namespace App\View\Composers;

use Illuminate\View\View;

class OccasionComposer
{
    public function compose(View $view): void
    {
        $occasionType = null;
        $message = '';
        $title = '';
        $user = auth()->user();

        if ($user && function_exists('isSpecialDay')) {
            if (!cache()->has('birthdate_' . $user->id) && isSpecialDay('birthdate')) {
                $birthdayQuotes = [
                    "امیدوارم روزت پر از خنده و شادی باشه.",
                    "برات آرزوی سلامتی، شادی و موفقیت در سال پیش رو دارم.",
                    "زندگی یک سفره و امروز شروع یک فصل جدیده.",
                    "تولدت بهانه‌ای برای شروع دوباره است. از سفرت لذت ببر!",
                    "جوانی هدیه طبیعته، اما پختگی یک اثر هنریه.",
                    "یک سال بزرگتر، یک سال عاقل‌تر.",
                    "امروز همه چیز درباره توست. از هر لحظه‌اش لذت ببر.",
                    "تولدت مبارک! امیدوارم بهترین سال زندگیت رو پیش رو داشته باشی."
                ];
                $occasionType = 'birthday';
                $title = 'تولدتان مبارک ' . ($user->name ?? 'عزیز') . '!';
                $message = $birthdayQuotes[array_rand($birthdayQuotes)];
                cache()->put('birthdate_' . $user->id, 'shown', now()->addHours(12));

            } elseif (!cache()->has('start_date_' . $user->id) && isSpecialDay('start_date')) {
                $startDateQuotes = [
                    "تبریک بابت رسیدن به یک نقطه عطف دیگر در مسیر شغلی‌تان!",
                    "به امید سالی سرشار از رشد، یادگیری و موفقیت.",
                    "تلاش و تعهد شما تأثیر بزرگی بر تیم ما داشته است.",
                    "سپاس بابت تعهد و مشارکت‌های ارزشمندتان در شرکت.",
                    "امیدواریم این سالگرد، شروعی برای موفقیت‌های بزرگ‌تر باشد.",
                    "اشتیاق و تعهد شما همواره الهام‌بخش ماست.",
                    "یک سال دیگر از همکاری گذشت. آینده شما در اینجا روشن است!",
                    "ممنون که با مهارت و تعهدتان در کنار ما هستید."
                ];
                $occasionType = 'anniversary';
                $title = 'سالگرد همکاریتان مبارک ' . ($user->name ?? 'عزیز') . '!';
                $message = $startDateQuotes[array_rand($startDateQuotes)];
                cache()->put('start_date_' . $user->id, 'shown', now()->addHours(12));
            }
        }

        $view->with('occasionType', $occasionType)->with('message', $message)->with('title', $title);
    }
}
