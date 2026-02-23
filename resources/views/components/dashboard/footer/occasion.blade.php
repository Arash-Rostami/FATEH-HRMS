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
                "تولدت بهانه‌ای برای شروع دوباره است. از سفرت لذت ببر!",
                "جوانی هدیه طبیعته، اما پختگی یک اثر هنریه.",
                "یک سال بزرگتر، یک سال عاقل‌تر.",
                "امروز همه چیز درباره توست. از هر لحظه‌اش لذت ببر.",
                "تولدت مبارک! امیدوارم بهترین سال زندگیت رو پیش رو داشته باشی."
            ];
            $occasionType = 'birthday';
            $title = 'تولدتان مبارک ' . ($user->forename ?? 'عزیز') . '!';
            $message = $birthdayQuotes[array_rand($birthdayQuotes)];
            cache()->put('birthdate' . $user->id, 'shown', now()->addHours(8));
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
            cache()->put('start_date' . $user->id, 'shown', now()->addHours(8));
        }
    }
@endphp

@if($occasionType)
    <div
        x-data="occasion"
        x-show="show"
        x-transition:enter="transition ease-out duration-400"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center px-4 pb-4 sm:pb-0"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>

        {{-- Confetti canvas --}}
        <canvas id="confetti-canvas" class="absolute inset-0 w-full h-full pointer-events-none z-[101]"></canvas>

        {{-- Modal card --}}
        <div
            x-transition:enter="transition ease-out duration-400"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-sm
               bg-[var(--md-sys-color-surface)]
               rounded-3xl overflow-hidden
               border border-[var(--md-sys-color-outline-variant)]
               shadow-[0_24px_64px_rgba(0,0,0,0.25)]
               z-[102]"
            dir="rtl"
        >
            {{-- Header band — tonal, not a gradient --}}
            <div class="relative overflow-hidden px-6 pt-8 pb-6 flex flex-col items-center gap-3
                    bg-[var(--md-sys-color-primary-container)]">

                {{-- Dot mesh decoration --}}
                <div class="absolute inset-0 opacity-[0.08] pointer-events-none"
                     style="background-image: radial-gradient(circle, var(--md-sys-color-on-primary-container) 1px, transparent 1px); background-size: 20px 20px;"></div>

                {{-- Glow orb --}}
                <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 w-40 h-40 rounded-full blur-3xl opacity-30 pointer-events-none"
                     style="background: var(--md-sys-color-primary)"></div>

                {{-- Icon badge --}}
                <div class="relative z-10 w-16 h-16 rounded-2xl
                        bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]
                        flex items-center justify-center shadow-md
                        animate-[occasion-bounce_1.2s_ease-in-out_infinite]">
                <span class="material-symbols-rounded text-4xl font-fill">
                    {{ $occasionType === 'birthday' ? 'cake' : 'workspace_premium' }}
                </span>
                </div>

                {{-- Occasion type chip --}}
                <span class="relative z-10 inline-flex items-center gap-1.5 px-3 py-1 rounded-xl
                         bg-[var(--md-sys-color-on-primary-container)]/10
                         border border-[var(--md-sys-color-on-primary-container)]/15
                         text-[11px] font-bold uppercase tracking-widest
                         text-[var(--md-sys-color-on-primary-container)]">
                {{ $occasionType === 'birthday' ? '🎂 تولد' : '🏅 سالگرد' }}
            </span>
            </div>

            {{-- Body --}}
            <div class="px-6 py-6 space-y-4 text-center">
                <h2 class="text-xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">
                    {{ $title }}
                </h2>

                {{-- Quote block --}}
                <div class="relative px-4 py-4 rounded-2xl
                        bg-[var(--md-sys-color-surface-variant)]/40
                        border border-[var(--md-sys-color-outline-variant)]/50">
                    {{-- Decorative quote mark --}}
                    <span class="absolute top-2 right-3 text-3xl leading-none
                             text-[var(--md-sys-color-primary)] opacity-30 font-serif select-none">
                    &ldquo;
                </span>
                    <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify relative z-10 pt-1">
                        {{ $message }}
                    </p>
                </div>

                {{-- CTA --}}
                <button
                    @click="close()"
                    class="w-full py-3 rounded-xl
                       bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]
                       text-sm font-bold
                       shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]
                       hover:brightness-95 active:scale-[0.98] transition-all duration-200
                       focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40"
                >
                    متشکرم!
                </button>
            </div>
        </div>
    </div>
@endif
