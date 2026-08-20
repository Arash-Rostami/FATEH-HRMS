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
