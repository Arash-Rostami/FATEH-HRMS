@props([
    'active' => false,
    'currentBasis' => null,
    'endYear' => \Morilog\Jalali\Jalalian::now()->getYear(),
    'basisOptions' => ['created' => 'ایجاد', 'updated' => 'بروزرسانی'],
    'setBasis' => 'setDateBasis',
])

<div x-data="{ open: false, pos: {} }" @keydown.escape.window="open = false" class="relative">
    <button type="button" x-ref="trigger"
            @click="pos = $refs.trigger.getBoundingClientRect().toJSON(); open = !open"
            title="فیلتر بازه تاریخ"
            :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': open, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !open }"
            class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
        <span class="material-symbols-rounded text-[18px]">date_range</span>
        @if($active)
            <span class="absolute -left-1 -top-1 h-2 w-2 rounded-full bg-[var(--md-sys-color-error)]"></span>
        @endif
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak dir="rtl"
             @click.away="if (!$refs.trigger.contains($event.target)) open = false"
             x-transition
             :style="{ position: 'fixed', top: (pos.bottom + 8) + 'px', left: pos.left + 'px' }"
             class="w-80 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-2.5 shadow-2xl z-50">
            <x-ui.forms.date-basis-filter
                apply="applyDateSpan" clear="clearDateSpan"
                :startYear="\Morilog\Jalali\Jalalian::now()->getYear() - 15"
                :endYear="$endYear"
                :basisOptions="$basisOptions"
                :currentBasis="$currentBasis"
                :setBasis="$setBasis"
            />
        </div>
    </template>
</div>