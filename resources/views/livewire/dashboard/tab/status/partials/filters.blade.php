<div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3
            sticky top-0 z-20
            bg-[var(--md-sys-color-surface)]/90 backdrop-blur-xl
            px-4 py-3 rounded-2xl
            border border-[var(--md-sys-color-outline-variant)]/15
            shadow-[0_2px_8px_0_rgb(0,0,0,0.06)]">

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-center sm:justify-start">
        <button
            @click="setFilter('all')"
            :class="activeFilter === 'all'
                ? 'bg-[var(--md-sys-color-on-surface)] text-[var(--md-sys-color-surface)] shadow-lg'
                : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)]'"
            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium
                   border border-transparent select-none whitespace-nowrap
                   transition-all duration-200"
        >
            <span class="material-symbols-rounded text-[17px]">groups</span>
            <span>همه</span>
        </button>

        @foreach(presenceCases() as $status)
            <button
                @click="setFilter('{{ $status->value }}')"
                :class="activeFilter === '{{ $status->value }}'
                    ? '{{ $status->activeClass() }}'
                    : '{{ $status->inactiveClass() }}'"
                class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-medium
                       border border-transparent select-none whitespace-nowrap
                       transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $status->icon() }}</span>
                <span>{{ $status->label() }}</span>
                <span
                    class="px-1.5 py-0.5 text-[11px] font-semibold rounded-md transition-colors"
                    :class="activeFilter === '{{ $status->value }}'
                        ? 'bg-white text-{{ $status->color() }}-700 font-bold'
                        : 'bg-{{ $status->color() }}-500 text-white'"
                    x-text="$wire.stats['{{ $status->value }}'] ?? 0"
                ></span>
            </button>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="relative group w-full sm:w-60">
        <span class="material-symbols-rounded text-[18px] absolute right-3 top-1/2 -translate-y-1/2
                     pointer-events-none text-[var(--md-sys-color-on-surface-variant)]/40
                     group-focus-within:text-[var(--md-sys-color-primary)] transition-colors">search</span>
        <input
            type="text"
            x-model.debounce.300ms="search"
            placeholder="جستجو..."
            class="w-full pl-4 pr-10 py-2 rounded-xl text-sm outline-none
                   bg-[var(--md-sys-color-surface-container-high)]
                   border border-[var(--md-sys-color-outline-variant)]/25
                   text-[var(--md-sys-color-on-surface)]
                   placeholder:text-[var(--md-sys-color-on-surface-variant)]/40
                   focus:border-[var(--md-sys-color-primary)]/50
                   focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/10
                   focus:bg-[var(--md-sys-color-surface-container-highest)]
                   transition-all duration-200"
        >
    </div>
</div>
