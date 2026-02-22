@props(['title' => null, 'description' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'glass-panel overflow-hidden rounded-2xl p-6 transition-all hover:shadow-[0_8px_30px_rgba(0,0,0,0.12)]']) }}>
    @if($title)
        <div class="mb-6 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)] pb-4">
            <div>
                <h3 class="text-lg font-bold leading-6 text-[var(--md-sys-color-on-surface)]">{{ $title }}</h3>
                @if($description)
                    <p class="mt-1 text-sm text-[var(--md-sys-color-on-surface-variant)]">{{ $description }}</p>
                @endif
            </div>
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
