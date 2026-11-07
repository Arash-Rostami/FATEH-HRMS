@props(['block' => []])

<div class="ai-report-keep flex flex-col gap-2 pb-3 border-b border-[var(--md-sys-color-outline-variant)]/60">
    <div class="flex items-center gap-3 min-w-0">
        <div class="print:hidden w-8 h-8 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0" aria-hidden="true">
            <span class="material-symbols-rounded text-base">description</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-[var(--md-sys-color-on-surface)] min-w-0" dir="auto">{{ $block['heading'] ?? '' }}</h1>
    </div>
    @if(filled($block['subtitle'] ?? null))
        <p wire:key="ai-report-title-subtitle" class="text-[13px] font-medium text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $block['subtitle'] }}</p>
    @endif
</div>
