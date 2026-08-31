@props(['title' => true, 'cards' => 3])

<div {{ $attributes->merge(['class' => 'flex-shrink-0 w-72 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-3 flex flex-col gap-3']) }}>
    @if($title)
        <div class="flex items-center gap-2 px-1">
            <x-ui.loaders.skeleton.bar width="w-1/2" height="h-3.5"/>
        </div>
    @endif
    @for($i = 0; $i < $cards; $i++)
        <x-ui.loaders.skeleton.card :lines="1"/>
    @endfor
</div>
