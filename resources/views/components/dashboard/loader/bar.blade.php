<div
    {{ $attributes->merge(['class' => 'fixed inset-0 z-[9999] flex items-center justify-center overflow-hidden']) }}
    style="background: radial-gradient(circle at center, color-mix(in srgb, var(--md-sys-color-surface-variant), transparent 95%) 0%, var(--md-sys-color-surface) 100%); backdrop-filter: blur(16px);">

    <div
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] opacity-20 pointer-events-none">
        <div class="absolute inset-0 rounded-full bg-[var(--md-sys-color-primary)] blur-[120px] animate-pulse"
             style="animation-duration: 4s;">
        </div>
    </div>

    <div
        class="relative flex flex-col items-center p-12 rounded-3xl border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface)]/10 shadow-2xl transition-all duration-500 hover:border-[var(--md-sys-color-outline-variant)]/50 hover:bg-[var(--md-sys-color-surface)]/20 hover:shadow-[0_0_40px_-10px_rgba(0,0,0,0.2)]">

        <div class="flex flex-col items-center gap-2 z-10">

            <h3 class="text-xl font-medium tracking-[0.2em] text-[var(--md-sys-color-on-surface)]"
                style="font-family: var(--font-sans);">
                ███░░
            </h3>
            <h6 class="text-xl font-medium tracking-[0.2em] text-[var(--md-sys-color-on-surface)]"
                style="font-family: var(--font-sans);">Processing ...
            </h6>
            <div class="relative h-px w-48 bg-[var(--md-sys-color-outline-variant)] overflow-hidden mt-2">
                <div
                    class="absolute inset-0 bg-gradient-to-r from-transparent via-[var(--md-sys-color-primary)] to-transparent w-1/2 h-full animate-[shimmer_1.5s_infinite]"></div>
            </div>
            <p class="mt-2 text-xs font-mono text-[var(--md-sys-color-outline)] uppercase tracking-widest opacity-70">
                Encrypting Data Packets
            </p>
        </div>

    </div>
</div>
