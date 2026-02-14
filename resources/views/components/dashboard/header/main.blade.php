<header
    class="sticky top-0 z-50 bg-[var(--header-bg)] backdrop-blur-md px-4 lg:px-8 flex justify-between items-center h-[60px] lg:h-[80px] transition-all duration-300 shrink-0
    border-b-4 border-[var(--header-border-color)]">
    <div class="flex items-center gap-4">
        <div class="relative group">
            <div
                class="absolute -inset-1 bg-gradient-to-r from-coral-500 to-salmon-400 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
            <img src="{{ Vite::asset('resources/assets/img/logo.png') }}"
                 alt="Fateh Logo"
                 class="animate-pop relative h-[32px] lg:h-[48px] w-auto transform transition-transform duration-500 hover:scale-105">
        </div>
    </div>
    <div class="flex flex-col items-end justify-center space-y-0.5">
        <h1 class="hidden sm:block font-bold tracking-widest text-white/95 "
            dir="rtl">
            اینترا، <span class="text-[#FF7F6E]">خانه دیجیتال ما</span>
        </h1>
        <div class="flex items-center gap-2 py-1 px-3 rounded-full bg-white/5 border border-white/10"
             dir="rtl">
            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
            <span
                class="text-[10px] lg:text-[11px] font-medium text-gray-400 leading-none truncate max-w-[150px] lg:max-w-none">
                        سید علی عزیز
            </span>
        </div>
    </div>
</header>
