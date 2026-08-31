@props(['lines' => 2])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] p-4 flex flex-col gap-2']) }}>
    <x-ui.loaders.skeleton.bar width="w-full" height="h-24"/>
    <x-ui.loaders.skeleton.bar width="w-4/5" height="h-3.5"/>
    @if($lines > 1)
        <x-ui.loaders.skeleton.bar width="w-2/5" height="h-3"/>
    @endif
</div>
