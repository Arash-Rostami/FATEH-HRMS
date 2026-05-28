@extends('layouts.app')

@section('minimal_layout', true)

@section('content')
    <div class="min-h-screen relative grid place-items-center p-4 md:p-8 overflow-hidden" dir="rtl"
         style="background-color: var(--md-sys-color-background);">

        {{-- Background Blobs --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[20%] -right-[10%] w-[50vw] h-[50vw] max-w-[600px] max-h-[600px] rounded-full blur-[100px] opacity-40 transition-colors duration-700"
                 style="background-color: color-mix(in srgb, var(--md-sys-color-secondary) 30%, transparent);"></div>
            <div class="absolute top-[60%] -left-[10%] w-[40vw] h-[40vw] max-w-[500px] max-h-[500px] rounded-full blur-[80px] opacity-30 transition-colors duration-700"
                 style="background-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent);"></div>
        </div>

        <div class="relative w-full max-w-3xl z-10 flex flex-col rounded-[2.5rem] glass-panel transition-all duration-500 overflow-hidden"
             style="background-color: color-mix(in srgb, var(--md-sys-color-surface) 95%, transparent); border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 50%, transparent);">

            {{-- Top Bar --}}
            <div class="h-2 w-full"
                 style="background: linear-gradient(90deg, var(--md-sys-color-secondary), color-mix(in srgb, var(--md-sys-color-secondary) 40%, var(--md-sys-color-primary)));"></div>

            <div class="p-8 md:p-14 lg:p-16 flex flex-col items-center text-center">

                {{-- Animated Icon --}}
                <div class="relative flex items-center justify-center w-24 h-24 md:w-28 md:h-28 rounded-[2rem] mb-12 group transition-all duration-500 hover:scale-[1.15] hover:rotate-6 cursor-pointer z-20">
                    <div class="absolute inset-0 rounded-[2rem] animate-ping opacity-80"
                         style="background-color: var(--md-sys-color-secondary-container); animation-duration: 1.2s;"></div>
                    <div class="absolute inset-0 rounded-[2rem] animate-ping opacity-50"
                         style="background-color: var(--md-sys-color-secondary); animation-duration: 2s; animation-delay: 0.4s;"></div>
                    <div class="absolute inset-0 rounded-[2rem] animate-ping opacity-30"
                         style="background-color: var(--md-sys-color-primary); animation-duration: 2.8s; animation-delay: 0.8s;"></div>

                    <div class="absolute -inset-6 rounded-full blur-3xl opacity-30 animate-[pulse_3s_infinite] group-hover:opacity-70 transition-opacity duration-500"
                         style="background-color: var(--md-sys-color-secondary);"></div>

                    <div class="absolute inset-0 rounded-[2rem] overflow-hidden backdrop-blur-md transition-all duration-500 group-hover:shadow-[0_0_40px_-5px]"
                         style="background: radial-gradient(circle at top left, var(--md-sys-color-secondary-container), transparent); border: 2px solid color-mix(in srgb, var(--md-sys-color-secondary) 40%, transparent); color: var(--md-sys-color-secondary);">
                        <div class="absolute inset-[-100%] opacity-0 group-hover:opacity-40 group-hover:animate-[spin_2s_linear_infinite] transition-opacity duration-300"
                             style="background: conic-gradient(from 0deg, transparent, var(--md-sys-color-secondary), transparent, var(--md-sys-color-primary), transparent);"></div>
                    </div>

                    <span class="material-symbols-rounded font-fill !text-5xl md:!text-7xl relative z-10 animate-[bounce_4s_infinite] group-hover:animate-none group-hover:scale-125 group-hover:rotate-12 transition-all duration-300 text-white">
                        hourglass_disabled
                    </span>
                </div>

                {{-- Error Code --}}
                <h1 class="text-8xl md:text-9xl font-black tracking-tighter mb-4 select-none"
                    style="background: linear-gradient(135deg, var(--md-sys-color-secondary) 0%, var(--md-sys-color-on-surface) 100%);
                           -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    419
                </h1>

                {{-- Title & Desc --}}
                <h2 class="text-2xl md:text-4xl font-bold mb-4 tracking-tight"
                    style="color: var(--md-sys-color-on-surface);">
                    نشست شما منقضی شده است
                </h2>
                <p class="text-base md:text-lg max-w-xl mx-auto mb-10 leading-relaxed font-medium"
                   style="color: var(--md-sys-color-on-surface-variant);">
                    به دلیل عدم فعالیت برای مدت طولانی، فرم یا نشست شما منقضی شده است. لطفاً صفحه را بارگذاری مجدد (رفرش) کنید و دوباره امتحان نمایید.
                </p>

                {{-- Info Pills --}}
                <div class="flex flex-wrap items-center justify-center gap-3 mb-14">
                    @auth
                        <div class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors glass-panel"
                             style="color: var(--md-sys-color-on-surface-variant); background-color: color-mix(in srgb, var(--md-sys-color-surface-variant) 40%, transparent);">
                            <span class="material-symbols-rounded font-fill text-lg">account_circle</span>
                            <span>{{ auth()->user()->name }}</span>
                        </div>
                    @endauth

                    <div class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors glass-panel"
                         style="color: var(--md-sys-color-on-surface-variant); background-color: color-mix(in srgb, var(--md-sys-color-surface-variant) 40%, transparent);">
                        <span class="material-symbols-rounded font-fill text-lg">schedule</span>
                        <span dir="ltr">{{ convertToPersian(toJalali(now())) }}</span>
                    </div>

                    <div class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-colors glass-panel"
                         style="color: var(--md-sys-color-on-surface-variant); background-color: color-mix(in srgb, var(--md-sys-color-surface-variant) 40%, transparent);">
                        <span class="material-symbols-rounded font-fill text-lg">link</span>
                        <span dir="ltr" class="truncate max-w-[150px]">{{ request()->path() }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full justify-center">
                    <button onclick="location.reload()" class="md3-btn ripple-effect w-full sm:w-auto !rounded-full !px-8 !py-4 !h-auto text-base">
                        <span class="material-symbols-rounded font-fill text-xl">refresh</span>
                        رفرش صفحه
                    </button>
                    <a href="{{ route('dashboard') }}" class="md3-btn-outlined ripple-effect w-full sm:w-auto !rounded-full !px-8 !py-4 !h-auto text-base">
                        <span class="material-symbols-rounded font-fill text-xl">space_dashboard</span>
                        بازگشت به پنل
                    </a>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 border-t"
                 style="background-color: color-mix(in srgb, var(--md-sys-color-surface-variant) 20%, transparent);
                        border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent);">
                <div class="flex items-center gap-3 select-none">
                    <img src="{{ asset('build/assets/img/logo.png') }}" alt="{{ config('app.name') }}" class="h-6 grayscale opacity-50" onerror="this.style.display='none'">
                    <span class="text-xs font-bold tracking-widest uppercase" style="color: var(--md-sys-color-on-surface-variant); opacity: 0.6;">
                        {{ config('app.name', 'Intra') }}
                    </span>
                </div>
                <span class="text-xs font-mono tracking-widest select-none" style="color: var(--md-sys-color-on-surface-variant); opacity: 0.5;" dir="ltr">
                    REF: {{ strtoupper(substr(md5(request()->ip() . now()->timestamp), 0, 8)) }}
                </span>
            </div>

        </div>
    </div>
@endsection
