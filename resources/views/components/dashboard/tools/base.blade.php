@props([
    'minimizable' => false,
    'onMinimize'  => 'minimize()',
    'onClose'     => 'closeModal()',
    'width'       => 'max-w-md',
])

<div
    {{ $attributes->merge(['class' => 'fixed inset-0 z-50 flex items-center justify-center bg-[var(--md-sys-color-primary)]/60']) }}
    x-transition x-cloak
>
    <div class="w-full {{ $width }} mx-4 overflow-hidden rounded-2xl shadow-lg"
         style="background:var(--md-sys-color-surface);box-shadow:0 24px 80px rgba(0,0,0,.28);">

        <div class="relative overflow-hidden px-5 py-4"
             style="background:linear-gradient(135deg,var(--md-sys-color-primary),color-mix(in srgb,var(--md-sys-color-primary) 78%,#000 22%));border-bottom:1px solid rgba(255,255,255,.08);">
            <div class="absolute inset-0 pointer-events-none opacity-25"
                 style="background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);animation:shimmer 6s linear infinite;"></div>

            <div class="relative flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 ring-white/15"
                         style="background:rgba(255,255,255,.12);">{{ $icon }}</div>
                    <div class="min-w-0 flex-1">{{ $heading }}</div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if($minimizable)
                        <button @click="{{ $onMinimize }}"
                                class="flex items-center justify-center rounded-xl border border-white/10 transition-all duration-150 hover:-translate-y-px hover:bg-white/14 active:scale-95"
                                style="width:40px;height:40px;background:rgba(255,255,255,.10);">
                            <span class="material-symbols-rounded" style="color:var(--md-sys-color-on-primary);font-size:20px;">remove</span>
                        </button>
                    @endif
                    <button @click="{{ $onClose }}"
                            class="flex items-center justify-center rounded-xl border border-white/10 transition-all duration-150 hover:-translate-y-px hover:bg-red-500/50 active:scale-95"
                            style="width:40px;height:40px;background:rgba(255,255,255,.10);">
                        <span class="material-symbols-rounded" style="color:var(--md-sys-color-on-primary);font-size:20px;">close</span>
                    </button>
                </div>
            </div>
        </div>

        {{ $slot }}
    </div>
</div>
