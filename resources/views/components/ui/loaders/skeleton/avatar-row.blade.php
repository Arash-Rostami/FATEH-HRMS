@props(['lines' => 2])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-2.5']) }}>
    <div class="w-10 h-10 rounded-xl flex-shrink-0 bg-[var(--md-sys-color-surface-variant)] animate-pulse"></div>
    <div class="flex-1 min-w-0 flex flex-col gap-1.5">
        <x-ui.loaders.skeleton.bar width="w-2/3" height="h-3"/>
        @if($lines > 1)
            <x-ui.loaders.skeleton.bar width="w-2/5" height="h-2.5"/>
        @endif
    </div>
</div>
