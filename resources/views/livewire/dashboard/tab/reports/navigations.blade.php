@php
    $depts = $this->departments;
@endphp

<x-ui.forms.filters
    searchModel="search"
    activeCondition="this.$wire.get('activeFilter') !== 'all'"
    clearAction="this.$wire.set('activeFilter', 'all'); this.$wire.set('search', '');"
    filterTitle="دپارتمان"
    placeholder="جستجوی گزارش…"
    open="{{ false }}"
>
    @if($depts->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2 w-full justify-start">
            <button @click="$wire.set('activeFilter', 'all')"
                    class="flex shrink-0 items-center gap-2 h-9 px-5 rounded-[14px] text-[13px] font-semibold border transition-all duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] hover:-translate-y-[1px] whitespace-nowrap"
                    :class="$wire.get('activeFilter') === 'all'
                        ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]/30 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] dark:shadow-[0_4px_12px_rgba(0,0,0,0.6)]'
                        : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:text-[var(--md-sys-color-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_5%,transparent)]'">
                همه
            </button>
            @foreach($depts as $d)
                <button
                    @click="$wire.set('activeFilter', $wire.get('activeFilter') === @js($d->code) ? 'all' : @js($d->code))"
                    class="flex shrink-0 items-center gap-2 h-9 px-5 rounded-[14px] text-[13px] font-semibold border transition-all duration-[var(--theme-transition-duration)] ease-[var(--theme-transition-easing)] hover:-translate-y-[1px] whitespace-nowrap"
                    :class="$wire.get('activeFilter') === @js($d->code)
                            ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-primary)]/30 shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] dark:shadow-[0_4px_12px_rgba(0,0,0,0.6)]'
                            : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] hover:border-[var(--md-sys-color-primary)]/40 hover:text-[var(--md-sys-color-primary)] hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_5%,transparent)]'">
                    {{ $d->displayLabel() }}
                </button>
            @endforeach
        </div>
    @endif
</x-ui.forms.filters>

<div class="px-4 md:px-12 pt-2 md:pt-4 flex items-center justify-end mb-2 md:mb-4 relative z-10">
    <div
        class="hidden md:flex bg-[var(--md-sys-color-surface-container-high)] p-1 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm">
        <button
            title=" نما کارتی"
            @click="view = 'card'; $wire.toggleView('card')"
            :class="view === 'card' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
            class="p-2 rounded-lg transition-all duration-300 flex items-center justify-center w-10 h-10">
            <span class="material-symbols-rounded">grid_view</span>
        </button>
        <button
            title="نما لیستی"
            @click="view = 'list'; $wire.toggleView('list')"
            :class="view === 'list' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
            class="p-2 rounded-lg transition-all duration-300 flex items-center justify-center w-10 h-10">
            <span class="material-symbols-rounded">view_list</span>
        </button>
    </div>
</div>
