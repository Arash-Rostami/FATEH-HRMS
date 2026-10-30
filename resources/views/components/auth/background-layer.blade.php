<div class="fixed inset-0 z-0 bg-black overflow-hidden pointer-events-none h-screen">
    <div class="w-full h-full relative" x-show="useVideo" x-cloak>
        @for ($i = 0; $i < 2; $i++)
            <video x-ref="v{{ $i }}" muted playsinline preload="none" disablepictureinpicture
                   oncontextmenu="return false" controlslist="nodownload noplaybackrate"
                   class="absolute inset-0 w-full h-full object-cover object-center select-none transition-opacity duration-[1200ms] will-change-[opacity]"
                   :class="active==={{ $i }} ? 'opacity-100' : 'opacity-0'"
                   @timeupdate="check({{ $i }})" @ended="swap({{ $i }})"></video>
        @endfor
        <div class="absolute inset-0 bg-black pointer-events-none"
             :style="`opacity: ${(100 - videoBrightness) / 100}`"></div>
    </div>

    <div class="w-full h-full" x-show="!useVideo || !videos.length" x-cloak
         :style="`filter: brightness(${videoBrightness}%)`">
        <img
            src="{{ asset(config('app.background_image')) }}"
            :src="customImage || '{{ asset(config('app.background_image')) }}'"
            alt="Background"
            fetchpriority="high"
            decoding="async"
            class="w-full h-full object-cover will-change-transform animate-kenburns"
        >
    </div>

    <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/10 mix-blend-overlay transition-colors duration-500"></div>
    <div
        class="absolute inset-0 bg-gradient-to-l from-black/80 via-black/20 to-transparent dark:from-black/95 dark:via-black/50 dark:to-transparent"></div>
</div>
