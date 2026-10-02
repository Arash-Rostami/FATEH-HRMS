@props(['id', 'text'])

<div
    class="absolute top-12 whitespace-nowrap opacity-60 group-hover:opacity-100 transition-opacity duration-300"
    :class="activeId == {{ $id }} ? '!opacity-100' : ''"
>
    <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
        {{ $text }}
    </span>
</div>