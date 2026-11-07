@props(['block' => []])

<div class="flex flex-col gap-2">
    @if(filled($block['heading'] ?? null))
        <div wire:key="ai-report-section-heading" class="flex items-center gap-3">
            <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $block['heading'] }}</h2>
            <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50" aria-hidden="true"></div>
        </div>
    @endif
    @foreach(($block['paragraphs'] ?? []) as $paragraph)
        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $paragraph }}</p>
    @endforeach
    @if(!empty($block['bullets']))
        <ul wire:key="ai-report-section-bullets" class="flex flex-col gap-1.5">
            @foreach($block['bullets'] as $bullet)
                <li class="flex items-start gap-2 text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="mt-2 w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)] shrink-0"></span>
                    <span dir="auto">{{ $bullet }}</span>
                </li>
            @endforeach
        </ul>
    @endif
    @if(filled($block['highlight'] ?? null))
        <p wire:key="ai-report-section-highlight" class="ai-report-keep rounded-xl border-s-2 border-[var(--md-sys-color-tertiary)] px-3 py-2 text-[12px] font-bold leading-6 bg-[color-mix(in_srgb,var(--md-sys-color-tertiary-container)_70%,transparent)] text-[var(--md-sys-color-on-tertiary-container)]" dir="auto">{{ $block['highlight'] }}</p>
    @endif
</div>
