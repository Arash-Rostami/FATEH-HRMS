@props(['id', 'date'])

<div
    class="absolute bottom-12 whitespace-nowrap px-2.5 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0"
    :class="activeId == {{ $id }} ? '!opacity-100 !translate-y-0' : ''"
>
    <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)]">
        {{ $date }}
    </span>
    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-variant)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"></div>
</div>