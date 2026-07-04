<div
    x-data="{ expanded: false }"
    x-init="$watch('active', value => expanded = value === {{ $faq->id }})"
    class="group flex flex-col rounded-2xl overflow-hidden transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-primary)]/30 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] bg-[var(--md-sys-color-surface)] w-full shadow-sm"
    :class="expanded ? 'ring-1 ring-[var(--md-sys-color-primary)] shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]' : ''"
>
    @php
        $badge = $presenter->badge($faq);
        $questionText = $presenter->questionText($faq);
    @endphp

    <button
        @click="toggle({{ $faq->id }})"
        class="w-full flex items-center justify-between gap-3 p-4 text-right transition-colors duration-200"
        :class="expanded ? 'bg-[var(--md-sys-color-surface-container-high)]' : 'bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
    >
        <div class="flex items-start gap-4 min-w-0 flex-1">
            <div class="mt-0.5 shrink-0 p-2 rounded-lg transition-colors duration-300"
                 :class="expanded ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] group-hover:bg-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)]'">
                <span class="material-symbols-rounded text-[20px] leading-none">forum</span>
            </div>

            <div class="flex flex-col gap-1.5 text-right min-w-0">
                <span x-html="highlight($el.textContent)" class="text-base font-bold text-[var(--md-sys-color-on-surface)] leading-snug transition-colors group-hover:text-[var(--md-sys-color-primary)] truncate">{{ $questionText }}</span>
                <div class="flex flex-wrap items-center gap-2 text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    <span class="bg-[var(--md-sys-color-surface-variant)] px-2 py-0.5 rounded-md font-medium">{{ $faq->category }}</span>
                    @if($faq->department)
                        <span class="text-[var(--md-sys-color-outline)]">•</span>
                        <span class="flex items-center gap-1" title="{{ $faq->department->tooltipLabel() }}">
                            <span class="material-symbols-rounded text-[14px] leading-none">call</span>
                            {{ $faq->department->displayLabel() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0 ms-auto">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md border whitespace-nowrap dark:shadow-[0_4px_12px_rgba(0,0,0,0.6)] animate-bubble-in {{ $badge['colorClasses'] }}">
                <span class="material-symbols-rounded text-[14px] leading-none {{ $badge['isUpdated'] ? 'text-[var(--md-sys-color-secondary)]' : '' }}">{{ $badge['icon'] }}</span>
                <span dir="rtl" class="text-[11px] font-semibold font-sans tracking-wide">{{ $badge['label'] }}: {{ $badge['date'] }}</span>
            </div>

            <span
                class="material-symbols-rounded transition-transform duration-300 text-[var(--md-sys-color-primary)] p-1 rounded-full hover:bg-[var(--md-sys-color-primary-container)] text-[24px] leading-none"
                :class="expanded ? 'rotate-180 bg-[var(--md-sys-color-primary-container)]' : ''"
            >
                expand_more
            </span>
        </div>
    </button>

    <div
        x-show="expanded"
        x-collapse.duration.300ms
        class="overflow-hidden bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]/50"
    >
        <div class="p-5 pr-[4.5rem] pl-6 relative">
            <div class="absolute right-6 top-6 bottom-6 w-0.5 bg-[var(--md-sys-color-outline-variant)]/50 rounded-full"></div>
            <div
                class="prose prose-sm prose-p:text-[var(--md-sys-color-on-surface)] prose-a:text-[var(--md-sys-color-primary)] max-w-none text-sm leading-7 text-justify"
                dir="rtl"
                x-html="highlightHtml(@js(str_replace('<a ', "<a target='_blank' class='hover:underline font-medium decoration-[var(--md-sys-color-primary)]/30 underline-offset-4 hover:decoration-[var(--md-sys-color-primary)] transition-all' ", $faq->answer)))"
            ></div>
        </div>
    </div>
</div>
