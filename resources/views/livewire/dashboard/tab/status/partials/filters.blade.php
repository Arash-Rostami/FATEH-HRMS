<div class="flex flex-col md:flex-row items-center justify-between gap-4 sticky top-0 z-20 backdrop-blur-xl bg-[var(--md-sys-color-surface)]/80 p-4 rounded-2xl shadow-sm border border-white/10 transition-all duration-300">

    {{-- Status Filters --}}
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar w-full md:w-auto p-1">
        @foreach([
            'onsite' => ['icon' => 'apartment', 'bg' => 'var(--md-sys-color-success-container)', 'text' => 'var(--md-sys-color-on-success-container)', 'label' => 'حاضر'],
            'remote' => ['icon' => 'laptop_chromebook', 'bg' => 'var(--md-sys-color-primary-container)', 'text' => 'var(--md-sys-color-on-primary-container)', 'label' => 'دورکار'],
            'busy' => ['icon' => 'do_not_disturb_on', 'bg' => 'var(--md-sys-color-error-container)', 'text' => 'var(--md-sys-color-on-error-container)', 'label' => 'مشغول'],
            'mission' => ['icon' => 'flight_takeoff', 'bg' => 'var(--md-sys-color-tertiary-container)', 'text' => 'var(--md-sys-color-on-tertiary-container)', 'label' => 'مأموریت']
        ] as $key => $meta)
            <button
                @click="setFilter('{{ $key }}')"
                :style="activeFilter === '{{ $key }}' ? 'background-color: {{ $meta['bg'] }}; color: {{ $meta['text'] }};' : ''"
                :class="{
                    'shadow-md scale-105 ring-1 ring-white/20': activeFilter === '{{ $key }}',
                    'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] hover:scale-105': activeFilter !== '{{ $key }}'
                }"
                class="relative group flex items-center gap-2 px-4 py-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] select-none whitespace-nowrap"
            >
                <span class="material-symbols-rounded text-[20px]">{{ $meta['icon'] }}</span>
                <span class="text-sm font-medium">{{ $meta['label'] }}</span>

                {{-- Count Badge --}}
                <span
                    class="ml-1 px-2 py-0.5 text-xs rounded-full bg-black/10 dark:bg-white/20 backdrop-blur-sm"
                    x-text="$wire.stats['{{ $key }}']"
                ></span>
            </button>
        @endforeach
    </div>

    {{-- Glass Search --}}
    <div class="relative w-full md:w-64 group">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--md-sys-color-on-surface-variant)]/50 pointer-events-none transition-colors group-focus-within:text-[var(--md-sys-color-primary)]">
            <span class="material-symbols-rounded">search</span>
        </span>
        <input
            type="text"
            x-model.debounce.300ms="searchQuery"
            placeholder="جستجو..."
            class="w-full pr-4 pl-10 py-2.5 rounded-full bg-[var(--md-sys-color-surface-container-high)]/50 backdrop-blur-md border border-white/10 focus:border-[var(--md-sys-color-primary)]/50 focus:bg-[var(--md-sys-color-surface-container-highest)] focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/10 transition-all duration-300 outline-none placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 text-sm text-[var(--md-sys-color-on-surface)]"
        >
    </div>
</div>
