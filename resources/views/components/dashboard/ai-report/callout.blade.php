@props(['block' => []])
@php([$box, $icon, $well] = match ($block['variant'] ?? 'info') {
    'warning' => ['bg-[color-mix(in_srgb,var(--md-sys-color-tertiary-container)_55%,transparent)] border-[var(--md-sys-color-tertiary)]/40', 'warning', 'bg-[var(--md-sys-color-warning-container)] text-[var(--md-sys-color-on-warning-container)]'],
    'decision' => ['bg-[color-mix(in_srgb,var(--md-sys-color-secondary-container)_55%,transparent)] border-[var(--md-sys-color-secondary)]/40', 'checklist', 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'],
    default => ['bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_45%,transparent)] border-[var(--md-sys-color-primary)]/30', 'info', 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'],
})

<div class="ai-report-keep flex flex-col gap-2.5 p-4 rounded-2xl border {{ $box }}">
    @if(filled($block['heading'] ?? null))
        <div wire:key="ai-report-callout-heading" class="flex items-center gap-2.5">
            <span class="flex items-center justify-center w-8 h-8 rounded-lg shrink-0 {{ $well }}" aria-hidden="true">
                <span class="material-symbols-rounded text-[18px]">{{ $icon }}</span>
            </span>
            <h3 class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $block['heading'] }}</h3>
        </div>
    @endif
    @foreach(($block['paragraphs'] ?? []) as $paragraph)
        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $paragraph }}</p>
    @endforeach
    @if(!empty($block['options']))
        <div wire:key="ai-report-callout-options" class="flex flex-col gap-2">
            @foreach($block['options'] as $option)
                <div class="flex items-start gap-2.5 rounded-xl bg-[var(--md-sys-color-surface)]/60 p-2.5">
                    <span class="shrink-0 inline-flex items-center justify-center min-w-6 h-6 px-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[11px] font-black text-[var(--md-sys-color-on-surface)]"><bdi>{{ $option['label'] ?? '' }}</bdi></span>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $option['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    @endif
    @if(filled($block['risk'] ?? null))
        <p wire:key="ai-report-callout-risk" class="text-[11.5px] leading-6 font-medium text-[var(--md-sys-color-on-surface-variant)] border-t border-[var(--md-sys-color-outline-variant)]/40 pt-2" dir="auto">{{ $block['risk'] }}</p>
    @endif
</div>
