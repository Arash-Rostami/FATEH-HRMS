@props([
    'cardClass' => 'relative min-h-[300px] overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm',
    'wrapClass' => 'w-full overflow-x-auto',
    'tableClass' => 'min-w-full w-full border-separate border-spacing-0 text-sm',
    'theadClass' => 'bg-[var(--md-sys-color-surface-container-high)] text-xs uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]',
    'tbodyClass' => '',
])

<div {{ $attributes->merge(['class' => $cardClass]) }}>
    <div class="{{ $wrapClass }}">
        <table class="{{ $tableClass }}">
            <thead class="{{ $theadClass }}">
                {{ $head }}
            </thead>
            <tbody class="{{ $tbodyClass }}">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
