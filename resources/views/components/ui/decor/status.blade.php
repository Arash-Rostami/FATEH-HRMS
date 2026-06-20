@props(['status'])

<div
    wire:key="{{ $attributes->get('wire:key') }}"
    class="relative w-full h-full ambient-status-wrapper transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] {{ $status->liftClass() }}"
    x-data="ambient('{{ $status->effectType() }}', '{{ $status->hex() }}')"
>
    <div
        class="absolute inset-0 pointer-events-none rounded-2xl z-0 ambient-effect-layer"
        style="--effect-color: {{ $status->hex() }};"
    >
        <template x-if="type === 'angry'">
            <div class="absolute inset-0 eb-container rounded-2xl" x-ref="container">
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
            <div class="absolute inset-0 rounded-2xl grumpy-dash"></div>
        </template>

        <template x-if="type === 'busy'">
            <div class="absolute inset-0 rounded-2xl busy-dash"></div>
        </template>

        <template x-if="type === 'remote'">
            <div class="absolute inset-0 rounded-2xl overflow-hidden">
                <svg class="absolute inset-0 w-full h-full remote-dash" style="border-radius: inherit;">
                    <rect x="0" y="0" width="100%" height="100%" rx="16" ry="16" />
                </svg>
            </div>
        </template>

        <template x-if="type === 'onsite'">
            <div class="absolute inset-0 rounded-2xl onsite-dash"></div>
        </template>
    </div>

    <div {{ $attributes->except('wire:key')->class('relative z-10 w-full h-full bg-inherit border-inherit shadow-inherit') }}>
        {{ $slot }}
    </div>
</div>
