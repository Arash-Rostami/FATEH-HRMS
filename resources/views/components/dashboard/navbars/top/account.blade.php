@props(['title' => 'حساب کاربری'])

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            class="group relative flex items-center gap-3 h-[40px] px-3 rounded-[12px] bg-[var(--md-sys-color-on-primary)]/5 border border-[var(--md-sys-color-on-primary)]/10 hover:bg-[var(--md-sys-color-on-primary)]/10 active:bg-[var(--md-sys-color-on-primary)]/20 transition-all duration-300 shadow-sm outline-none"
        {{ $attributes->merge(['class' => '']) }}>

        <div
            class="w-8 h-8 rounded-[8px] bg-[var(--md-sys-color-surface)] flex items-center justify-center overflow-hidden shrink-0 shadow-sm ">
            @if(auth()->check())
                <x-ui.avatar
                    :existingImage="auth()->user()?->profile->image"
                    alt="{{ auth()->user()->name }}"
                />

            @else
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-[20px]">
                    person
                </span>
            @endif
        </div>

        <div class="hidden md:flex flex-col items-start text-right leading-none gap-0.5">
            <div
                class="text-[12px] font-bold truncate max-w-[100px]">{{ auth()->user()->name ?? 'مهمان' }}</div>
            @auth
                <div class="text-[9px] opacity-70 font-mono tracking-wider">
                    {{ auth()->user()->profile?->personnel_id ?? '' }}
                </div>
            @endauth
        </div>

        <span class="material-symbols-rounded text-[20px] opacity-70 group-hover:opacity-100 transition-all"
              :class="open ? 'rotate-180' : ''">expand_more</span>

        <x-ui.modals.tooltip :text="$title" position="bottom"/>
    </button>

    <div x-show="open" @click.outside="open = false"
         class="absolute left-0 mt-2 w-56 bg-[var(--md-sys-color-surface)] rounded-2xl shadow-2xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden z-50 text-[var(--md-sys-color-on-surface)] animate-slide-down"
         style="display: none;">

        <div class="p-2 space-y-1">
            <div
                class="md:hidden px-3 py-2 border-b border-[var(--md-sys-color-outline-variant)]/10 mb-1 opacity-70 text-xs text-right">
                {{ auth()->user()->name ?? 'مهمان' }}
            </div>

            <a href="{{ url('/profile?activeTab=info') }}"
               target="_blank"
               rel="noopener noreferrer"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors group">
                        <span
                            class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] group-hover:scale-110 transition-transform">person</span>
                پروفایل کاربری
            </a>

            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/10 my-1 mx-2"></div>

            <div class="px-1">
                <livewire:auth.logout-button/>
            </div>
        </div>
    </div>
</div>
