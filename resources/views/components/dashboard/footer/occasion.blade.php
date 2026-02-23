@php
    $occasionType = null;
    $message = '';
    $title = '';
    $user = auth()->user();

    if (function_exists('isSpecialDay')) {
        if (isSpecialDay('birthdate')) {
             $birthdayQuotes = [
                "امیدوارم روزت پر از خنده و شادی باشه.",
                "برات آرزوی سلامتی، شادی و موفقیت در سال پیش رو دارم.",
                "زندگی یک سفره و امروز شروع یک فصل جدیده.",
                "تولدت بهانه ای برای شروع دوباره است. از سفرت لذت ببر!",
                "جوانی هدیه طبیعته، اما پختگی یک اثر هنریه.",
                "یک سال بزرگتر، یک سال عاقل‌تر.",
                "امروز همه چیز درباره توست. از هر لحظه‌اش لذت ببر.",
                "تولدت مبارک! امیدوارم بهترین سال زندگیت رو پیش رو داشته باشی."
            ];
            $occasionType = 'birthday';
            $title = 'تولدتان مبارک ' . ($user->forename ?? 'عزیز') . '!';
            $message = $birthdayQuotes[array_rand($birthdayQuotes)];
            cache()->put('birthdate'.$user->id, 'shown', now()->addHours(8));
        } elseif (isSpecialDay('start_date')) {
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
            $title = 'سالگرد همکاریتان مبارک ' . ($user->forename ?? 'عزیز') . '!';
            $message = $startDateQuotes[array_rand($startDateQuotes)];
            cache()->put('start_date'.$user->id, 'shown', now()->addHours(8));
        }
    }
@endphp

@if($occasionType)
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => {
            if(window.Confetti) window.Confetti.start('confetti-canvas');
        }, 100)"
        class="fixed inset-0 z-[100] flex items-center justify-center px-4"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="show = false"></div>
        <canvas id="confetti-canvas" class="absolute inset-0 w-full h-full pointer-events-none z-[101]"></canvas>

        <div class="relative w-full max-w-md bg-[var(--md-sys-color-surface)] rounded-3xl shadow-2xl border border-[var(--md-sys-color-outline-variant)] overflow-hidden z-[102]">
            <div class="h-32 bg-gradient-to-br from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_center,_var(--md-sys-color-on-primary)_2px,_transparent_0)] bg-[length:24px_24px]"></div>
                <span class="material-symbols-rounded text-6xl text-white drop-shadow-lg animate-bounce">
                    {{ $occasionType === 'birthday' ? 'cake' : 'workspace_premium' }}
                </span>
            </div>

            <div class="p-6 md:p-8 text-center space-y-4">
                <h2 class="text-2xl font-bold text-[var(--md-sys-color-on-surface)]">
                    {{ $title }}
                </h2>
                <p class="text-[var(--md-sys-color-on-surface-variant)] text-base leading-relaxed italic">
                    &ldquo;{{ $message }}&rdquo;
                </p>
                <div class="pt-4">
                    <button
                        @click="show = false"
                        class="w-full py-3 px-6 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold shadow-lg hover:shadow-xl transition-all duration-200"
                    >
                        متشکرم!
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
