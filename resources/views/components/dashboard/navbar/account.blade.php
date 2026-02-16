<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            title="حساب کاربری"
            class="flex items-center gap-3 pl-2 pr-1 py-1 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:bg-[var(--md-sys-color-on-primary)]/20 transition-all duration-200 group border border-transparent hover:border-[var(--md-sys-color-on-primary)]/10">

        <div
            class="w-9 h-9 rounded-full bg-[var(--md-sys-color-surface)] border-2 border-[var(--md-sys-color-primary-container)] flex items-center justify-center overflow-hidden">
            @if(auth()->check() && auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-full h-full object-cover">
            @else
                <span
                    class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-[20px]">person</span>
            @endif
        </div>

        <div class="hidden md:flex flex-col items-start text-right leading-none gap-0.5">
            <div
                class="text-[13px] font-bold truncate max-w-[120px]">{{ auth()->user()->name ?? 'مهمان' }}</div>
            @auth
                <div class="text-[10px] opacity-70 font-mono tracking-wider">
                    {{ auth()->user()->profile?->personnel_id ?? '' }}
                </div>
            @endauth
        </div>

        <span class="material-symbols-rounded text-[20px] opacity-70 group-hover:opacity-100 transition-all"
              :class="open ? 'rotate-180' : ''">expand_more</span>
    </button>

    <!-- dropdown unchanged -->
    <div x-show="open" @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="absolute left-0 mt-2 w-56 bg-[var(--md-sys-color-surface)] rounded-2xl shadow-2xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden z-50 text-[var(--md-sys-color-on-surface)]"
         style="display: none;">

        <div class="p-2 space-y-1">
            <div
                class="md:hidden px-3 py-2 border-b border-[var(--md-sys-color-outline-variant)]/10 mb-1 opacity-70 text-xs">
                {{ auth()->user()->name ?? 'مهمان' }}
            </div>

            <a href="{{ url('/profile') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors">
                        <span
                            class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">person</span>
                پروفایل کاربری
            </a>

            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

            <div class="px-1">
                <livewire:auth.logout-button/>
            </div>
        </div>
    </div>
</div>
