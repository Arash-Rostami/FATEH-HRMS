@props([
    'months',
    'title' => 'فیلتر تاریخ',
    'allLabel' => 'همه ماه‌ها',
])

@if($months->isNotEmpty())
    <div x-data="{ open: false }" @click.outside="open = false" class="relative hidden md:block">
        <button
            type="button"
            title="{{ $title }}"
            @click="open = !open"
            class="flex items-center justify-center w-8 h-8 rounded-md transition-all duration-200"
            :class="open || month ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/40'"
        >
            <span class="material-symbols-rounded text-[18px]">calendar_month</span>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute top-full mt-1 left-0 z-50 min-w-[10rem] bg-[var(--md-sys-color-surface-container-highest)] rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-lg p-1 flex flex-col gap-0.5"
        >
            <button
                @click="month = ''; open = false"
                :class="!month ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                class="px-3 h-9 rounded-lg text-xs font-medium text-right transition-colors duration-150"
            >{{ $allLabel }}
            </button>
            @foreach($months as $m)
                <button
                    @click="month = @js($m['key']); open = false"
                    :class="month === @js($m['key']) ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                    class="px-3 h-9 rounded-lg text-xs font-medium text-right transition-colors duration-150"
                >{{ $m['key'] }}</button>
            @endforeach
        </div>
    </div>
@endif
