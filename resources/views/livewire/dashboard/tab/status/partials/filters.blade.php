<div class="flex flex-col md:flex-row items-center justify-between gap-4 sticky top-0 z-20 backdrop-blur-xl bg-[var(--md-sys-color-surface)]/80 p-4 rounded-2xl shadow-sm border border-white/10 transition-all duration-300">

    {{-- Status Filters --}}
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar w-full md:w-auto p-1">
        @foreach([
            'onsite' => ['icon' => 'apartment', 'active_class' => 'bg-emerald-500 text-white shadow-emerald-500/30', 'inactive_class' => 'text-emerald-400 hover:bg-emerald-500/10', 'label' => 'حاضر'],
            'remote' => ['icon' => 'laptop_chromebook', 'active_class' => 'bg-sky-500 text-white shadow-sky-500/30', 'inactive_class' => 'text-sky-400 hover:bg-sky-500/10', 'label' => 'دورکار'],
            'busy' => ['icon' => 'do_not_disturb_on', 'active_class' => 'bg-rose-500 text-white shadow-rose-500/30', 'inactive_class' => 'text-rose-400 hover:bg-rose-500/10', 'label' => 'مشغول'],
            'mission' => ['icon' => 'flight_takeoff', 'active_class' => 'bg-amber-500 text-white shadow-amber-500/30', 'inactive_class' => 'text-amber-400 hover:bg-amber-500/10', 'label' => 'مأموریت']
        ] as $key => $meta)
            <button
                @click="setFilter('{{ $key }}')"
                :class="activeFilter === '{{ $key }}'
                    ? '{{ $meta['active_class'] }} shadow-lg scale-105 ring-1 ring-white/20'
                    : 'bg-[var(--md-sys-color-surface-container-high)] {{ $meta['inactive_class'] }} hover:scale-105'"
                class="relative group flex items-center gap-2 px-4 py-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] select-none whitespace-nowrap"
            >
                <span class="material-symbols-rounded text-[20px]">{{ $meta['icon'] }}</span>
                <span class="text-sm font-medium">{{ $meta['label'] }}</span>

                {{-- Count Badge --}}
                <span
                    class="ml-1 px-2 py-0.5 text-xs rounded-full backdrop-blur-sm transition-colors"
                    :class="activeFilter === '{{ $key }}' ? 'bg-white/20 text-white' : 'bg-black/5 dark:bg-white/10 text-[var(--md-sys-color-on-surface-variant)]'"
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
