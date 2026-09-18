@php
    $badge = $presenter->badge($faq);
    $questionHtml = renderInline($faq->question ?: 'بدون عنوان', 300);
@endphp

<div
    wire:key="faq-card-{{ $faq->id }}"
    data-rf="faqs-{{ $faq->id }}"
    class="slide-up group flex flex-col w-full overflow-hidden transition-all duration-300 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] hover:border-[var(--md-sys-color-primary)] hover:shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:hover:shadow-[0_12px_40px_rgba(0,0,0,0.6)]"
>
    <div @click="toggle({{ $faq->id }})"
         class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 p-4 bg-transparent cursor-pointer">
        <div class="flex flex-1 items-start gap-4 min-w-0 basis-52">
            <div class="mt-0.5 flex shrink-0 items-center justify-center w-9 h-9 transition-all duration-300 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] group-hover:scale-110 group-hover:bg-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)]">
                <span class="material-symbols-rounded text-[20px] leading-none" aria-hidden="true">forum</span>
            </div>

            <div class="flex flex-col gap-1.5 min-w-0 text-right">
                <h3 id="faq-q-{{ $faq->id }}"
                    x-html="highlightHtml(@js($questionHtml), $wire.search)"
                    class="rich-colors m-0 text-base font-bold leading-snug transition-colors duration-300 text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)]">{!! $questionHtml !!}</h3>

                <div class="flex flex-wrap items-center gap-2 text-[11px] opacity-80 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="px-2 py-0.5 font-medium rounded-md bg-[var(--md-sys-color-surface-variant)]">{{ $faq->category }}</span>
                    @if($faq->department)
                        <span class="text-[var(--md-sys-color-outline)]" aria-hidden="true">&#8226;</span>
                        <span class="flex items-center gap-1" title="{{ $faq->department->tooltipLabel() }}">
                            <span class="material-symbols-rounded text-[14px] leading-none" aria-hidden="true">call</span>
                            {{ $faq->department->displayLabel() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0 max-ms-auto">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 whitespace-nowrap rounded-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] animate-bubble-in {{ $badge['colorClasses'] }}">
                <span class="material-symbols-rounded text-[14px] leading-none {{ $badge['isUpdated'] ? 'text-[var(--md-sys-color-secondary)]' : '' }}" aria-hidden="true">{{ $badge['icon'] }}</span>
                <span dir="rtl" class="font-sans text-[11px] font-semibold tracking-wide">{{ $badge['label'] }}: {{ $badge['date'] }}</span>
            </div>
            <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-300"
                  :class="active === {{ $faq->id }} ? 'rotate-180' : ''" aria-hidden="true">expand_more</span>
        </div>
    </div>

    <div x-show="active === {{ $faq->id }}" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         role="region"
         aria-labelledby="faq-q-{{ $faq->id }}"
         class="bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)]">
        <div class="py-4 pr-[4.25rem] pl-5">

            <div class="flex items-center gap-2 mb-2.5">
                <span class="material-symbols-rounded text-[16px] leading-none text-[var(--md-sys-color-primary)]" aria-hidden="true">reply</span>
                <span class="text-[11px] font-bold tracking-wide text-[var(--md-sys-color-primary)]">پاسخ</span>
                <span class="flex-1 h-px bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]" aria-hidden="true"></span>
            </div>

            <div class="grid grid-cols-[3px_1fr] gap-x-4">
                <div class="rounded-full bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]" aria-hidden="true"></div>
                <div
                    class="max-w-none text-sm leading-7 text-justify prose prose-sm prose-p:text-[var(--md-sys-color-on-surface)] prose-a:text-[var(--md-sys-color-primary)] rich-colors"
                    dir="rtl"
                    x-html="highlightHtml(@js(str_replace('<a ', "<a target='_blank' class='hover:underline font-medium decoration-[color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)] underline-offset-4 hover:decoration-[var(--md-sys-color-primary)] transition-all duration-300' ", $faq->answer)), $wire.search)"
                ></div>
            </div>

        </div>
    </div>
</div>
