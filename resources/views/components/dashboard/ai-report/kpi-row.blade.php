@props(['block' => []])

<div class="ai-report-keep flex flex-col gap-2 print:gap-1">
    @if(filled($block['heading'] ?? null))
        <h2 wire:key="ai-report-kpi-heading" class="text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $block['heading'] }}</h2>
    @endif
    <div class="grid grid-cols-2 lg:grid-cols-4 print:grid-cols-4 gap-3">
        @foreach(($block['items'] ?? []) as $item)
            @php($ink = match ($item['tone'] ?? '') {
                'success' => 'bg-[var(--md-sys-color-success)]',
                'warning' => 'bg-[var(--md-sys-color-warning)]',
                'danger' => 'bg-[var(--md-sys-color-error)]',
                'info' => 'bg-[var(--md-sys-color-secondary)]',
                default => 'bg-[var(--md-sys-color-outline)]',
            })
            <div class="relative flex flex-col gap-1 p-3.5 rounded-xl bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden">
                <p class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] truncate" dir="auto">{{ $item['label'] ?? '' }}</p>
                <p class="{{ ($block['type'] ?? '') === 'facts' ? 'text-[12.5px] font-bold break-words' : 'text-base font-black tracking-tight break-words tabular-nums' }} text-[var(--md-sys-color-on-surface)]"><bdi>{{ $item['value'] ?? '' }}</bdi></p>
                @if(filled($item['hint'] ?? null))
                    <p wire:key="ai-report-kpi-hint" class="text-[10.5px] leading-5 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $item['hint'] }}</p>
                @endif
                <span class="absolute bottom-0 inset-x-6 h-0.5 rounded-t {{ $ink }}" aria-hidden="true"></span>
            </div>
        @endforeach
    </div>
</div>
