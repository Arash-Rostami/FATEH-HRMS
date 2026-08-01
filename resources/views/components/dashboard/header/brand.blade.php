<div class="sticky !top-0 z-50 will-change-transform"
     id="header"
     style="transition: transform 280ms cubic-bezier(0.4,0,0.2,1)"
     :class="isVisible ? 'translate-y-0' : '-translate-y-full'">

    <header dir="rtl"
            class="shrink-0
                   bg-[var(--header-bg)]
                   border-b-4 border-[var(--header-border-color)]
                   h-[60px] lg:h-[80px]
                   px-4 lg:px-8"
            x-data="greeting('{{ addslashes(greeting()) }}')">

        <div
            class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent pointer-events-none"></div>
        <div class="relative h-full flex items-center justify-between gap-4">

            <div class="flex items-center gap-3 min-w-0">
                <h1 class="hidden sm:block
                            font-extrabold tracking-[0.18em] uppercase
                            text-base lg:text-xl
                            leading-none select-none whitespace-nowrap
                            bg-gradient-to-l from-white via-white/90 to-white/60
                            bg-clip-text text-transparent">
                    {{ config('app.name') }}،
                    <span class="bg-[var(--header-border-color)] bg-clip-text text-transparent font-normal">
                        {{ config('app.slogan') }}
                    </span>
                </h1>

                <span class="hidden sm:block h-7 w-px bg-white/15 shrink-0"></span>

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
                                 transition-opacity duration-700 tracking-wide leading-relaxed truncate transition-colors duration-500 ease-out group-hover:text-white"></span>
                </div>
            </div>

            <div class="group relative flex items-center shrink-0">
                <div class="flex items-center gap-3 shrink-0 group">
                    @if(config('app.user_use_company_logo'))
                        <button type="button"
                                x-data="{ flipped: false }"
                                @click="flipped = !flipped"
                                class="relative h-[30px] lg:h-[35px] w-[110px] lg:w-[130px] shrink-0 perspective-1000 cursor-pointer
                       transition-transform duration-500 ease-out group-hover:scale-105"
                                title="{{ config('app.organization_name_en') }} / {{ config('app.name_en') }}">
                            <div class="absolute inset-0 preserve-3d transition-transform duration-700 ease-out"
                                 :class="flipped ? 'rotate-x-180' : ''">
                                <img src="{{ asset(config('app.company_logo')) }}"
                                     alt="{{ config('app.organization_name_en') }}"
                                     class="absolute inset-0 h-full w-full object-contain object-center backface-hidden">
                                <div class="absolute inset-0 backface-hidden rotate-x-180">
                                    <img src="{{ asset(tenantLogo(true)) }}"
                                         alt="{{ config('app.name_en') }}"
                                         class="absolute inset-0 h-full w-full object-contain object-center hidden dark:block">
                                    <img src="{{ asset(tenantLogo(false)) }}"
                                         alt="{{ config('app.name_en') }}"
                                         class="absolute inset-0 h-full w-full object-contain object-center dark:hidden">
                                </div>
                            </div>
                        </button>
                    @else
                        <img src="{{ asset(tenantLogo(true)) }}"
                             alt="{{ config('app.name_en') }}" title="{{ config('app.name_en') }}"
                             class="relative h-[30px] lg:h-[35px] w-auto transition-all duration-500 ease-out
                    group-hover:scale-105 group-hover:drop-shadow-[0_0_10px_rgba(255,127,110,0.45)] hidden dark:block">
                        <img src="{{ asset(tenantLogo(false)) }}"
                             alt="{{ config('app.name_en') }}" title="{{ config('app.name_en') }}"
                             class="relative h-[30px] lg:h-[35px] w-auto transition-all duration-500 ease-out
                    group-hover:scale-105 group-hover:drop-shadow-[0_0_10px_rgba(255,127,110,0.45)] dark:hidden">
                    @endif
                </div>

        </div>
    </header>
</div>
