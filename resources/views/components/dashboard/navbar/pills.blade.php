@props(['user'])

@if($user?->profile)
    <div {{ $attributes->merge(['class' => 'hidden xl:flex items-center gap-1']) }}>

        {{-- Birthday Badge --}}
        <div class="relative w-10 h-10 rounded-full flex items-center justify-center cursor-help group overflow-visible transition-all duration-200" title="روزهای باقیمانده تا تولد">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 group-hover:text-pink-400 transition-colors">cake</span>
            <span class="absolute top-1 right-0 translate-x-1/2 -translate-y-1/2 w-5 h-5 rounded-xl bg-pink-400 opacity-20 pointer-events-none animate-subtle-pulse"></span>
            <span class="absolute top-1 right-0 translate-x-1/2 -translate-y-1/2 min-w-[18px] h-4 px-1.5 rounded-full bg-pink-500 text-white text-[9px] font-bold flex items-center justify-center">
                {{ $user->profile->daysUntil($user->profile->birthdate) ?? '-' }}
            </span>
            <span class="absolute -left-1 bottom-1 w-1.5 h-1.5 bg-white/60 rounded-full opacity-30 transform rotate-12"></span>
            <span class="absolute left-1 -bottom-0.5 w-1 h-1 bg-white/40 rounded-full opacity-25"></span>
        </div>

        {{-- Work Anniversary Badge --}}
        <div class="relative w-10 h-10 rounded-full flex items-center justify-center cursor-help group overflow-visible transition-all duration-200" title="سابقه همکاری (روز)">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 group-hover:text-emerald-400 transition-colors">work_history</span>
            <span class="absolute top-1 right-0 translate-x-1/2 -translate-y-1/2 w-5 h-5 rounded-xl bg-emerald-400 opacity-20 pointer-events-none animate-subtle-pulse"></span>
            <span class="absolute top-1 right-0 translate-x-1/2 -translate-y-1/2 min-w-[18px] h-4 px-1.5 rounded-full bg-emerald-500 text-white text-[9px] font-bold flex items-center justify-center">
                {{ $user->profile->daysPassed($user->profile->start_date) ?? '-' }}
            </span>
            <span class="absolute -right-0.5 bottom-1 w-1.5 h-1.5 bg-white/60 rounded-full opacity-30 transform -rotate-6"></span>
            <span class="absolute -left-0.5 top-1 w-1 h-1 bg-white/40 rounded-full opacity-25"></span>
        </div>

    </div>
@endif
