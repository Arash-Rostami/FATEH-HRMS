@props(['columns' => 4, 'rows' => 6])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden']) }}>
    <div class="flex items-center gap-4 px-5 py-3 bg-[var(--md-sys-color-surface-container-low)] border-b border-[var(--md-sys-color-outline-variant)]/40">
        @for($i = 0; $i < $columns; $i++)
            <x-ui.loaders.skeleton.bar width="flex-1" height="h-3"/>
        @endfor
    </div>
    @for($r = 0; $r < $rows; $r++)
        <div @class([
                'flex items-center gap-4 px-5 py-3.5',
                'border-b border-[var(--md-sys-color-outline-variant)]/20' => $r < $rows - 1,
            ])>
            @for($i = 0; $i < $columns; $i++)
                <x-ui.loaders.skeleton.bar width="flex-1" height="h-3.5"/>
            @endfor
        </div>
    @endfor
</div>
