<header dir="rtl"
        class="sticky top-0 z-50 shrink-0
               bg-[var(--header-bg)]
               border-b-4 border-[var(--header-border-color)]
               h-[60px] lg:h-[80px]
               px-4 lg:px-8
               transition-all duration-300"
        x-data="greeting('{{ addslashes(greeting()) }}')">

    <div
        class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent pointer-events-none"></div>
    <div class="relative h-full flex items-center justify-between gap-4">

        {{-- ── RIGHT CLUSTER ──────────────────────────────────── --}}
        <div class="flex items-center gap-3 min-w-0">
            {{-- Title --}}
            <h1 class="hidden sm:block
                        font-extrabold tracking-[0.18em] uppercase
                        text-base lg:text-xl
                        leading-none select-none whitespace-nowrap
                        bg-gradient-to-l from-white via-white/90 to-white/60
                        bg-clip-text text-transparent">
                اینتـرا،&nbsp;<span class="bg-gradient-to-l from-[#FF7F6E] to-[#ffb199] bg-clip-text text-transparent">خانه دیجیتـال ما</span>
            </h1>

            <span class="hidden sm:block h-7 w-px bg-white/15 shrink-0"></span>

            {{-- Greeting --}}
            <div class="flex items-center gap-2 min-w-0">
                <span class="relative flex h-1.5 w-1.5 shrink-0">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                </span>
                <span x-text="displayed"
                      class="text-[11px] lg:text-xs font-medium
                             text-white/50 leading-none
                             max-w-[180px] lg:max-w-[380px]
                             overflow-hidden whitespace-nowrap block
                             transition-opacity duration-700 tracking-wide leading-relaxed max-w-[200px] lg:max-w-[400px] truncate transition-colors duration-500 ease-out group-hover:text-white"></span>
            </div>
        </div>

        {{-- ── LOGO ────────────────────────────────────────────── --}}
        <div class="group relative flex items-center shrink-0">
            <div class="absolute -inset-3 rounded-full
                        bg-gradient-to-r from-[#FF7F6E]/15 to-[#ffb199]/10
                        blur-xl opacity-0 group-hover:opacity-100
                        transition duration-700 pointer-events-none"></div>
            <img src="{{ Vite::asset('resources/assets/img/logo.png') }}"
                 alt="Fateh Logo"
                 class="relative h-[30px] lg:h-[44px] w-auto
                        transition-all duration-500 ease-out
                        group-hover:scale-105
                        group-hover:drop-shadow-[0_0_10px_rgba(255,127,110,0.45)]">
        </div>

    </div>
</header>
