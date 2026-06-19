@props(['status'])

<div
    wire:key="{{ $attributes->get('wire:key') }}"
    class="relative w-full h-full ambient-status-wrapper"
    x-data="ambientStatus('{{ $status->effectType() }}', '{{ $status->hex() }}')"
>
    <!-- Background overlay for effects to not mess with the grid layout -->
    <div
        class="absolute inset-0 pointer-events-none rounded-2xl z-0 ambient-effect-layer"
        style="--effect-color: {{ $status->hex() }};"
    >
        <template x-if="type === 'angry'">
            <div class="absolute inset-0 eb-container" x-ref="container">
                <canvas x-ref="canvas" class="absolute inset-0 w-full h-full"></canvas>
                <div class="absolute inset-0 eb-glow-1 opacity-60"></div>
                <div class="absolute inset-0 eb-glow-2 opacity-40"></div>
            </div>
        </template>

        <template x-if="type === 'mission'">
            <div class="absolute inset-0 rounded-2xl overflow-hidden">
                <svg class="absolute inset-0 w-full h-full mission-dash" style="border-radius: inherit;">
                    <rect x="0" y="0" width="100%" height="100%" rx="16" ry="16" />
                </svg>
            </div>
        </template>

        <template x-if="type === 'grumpy'">
             <div class="absolute inset-0 rounded-2xl grumpy-noise"></div>
        </template>

        <template x-if="type === 'busy'">
            <div class="absolute inset-0 rounded-2xl busy-pulse"></div>
        </template>

        <template x-if="type === 'remote'">
             <div class="absolute inset-0 rounded-2xl remote-glow"></div>
        </template>

        <template x-if="type === 'onsite'">
             <div class="absolute inset-0 rounded-2xl onsite-lift"></div>
        </template>

    </div>

    <!-- Original Card Content -->
    <div {{ $attributes->except('wire:key') }} class="{{ $attributes->get('class') }} relative z-10 w-full h-full bg-inherit border-inherit shadow-inherit">
        {{ $slot }}
    </div>
</div>
