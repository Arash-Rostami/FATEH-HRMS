@props(['icon', 'title', 'count' => null, 'countLabel' => 'مورد'])

<div class="flex items-center gap-3 mb-4">
    <div
        class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
        <span class="material-symbols-rounded text-base font-fill">{{ $icon }}</span>
    </div>
    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">{{ $title }}</h2>
    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    @if($count !== null)
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-xl
                     bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
            {{ $count }} {{ $countLabel }}
        </span>
    @endif
</div>
