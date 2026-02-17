<div x-data="background"
     :class="($store.background.enabled || $store.background.patternEnabled) ? 'bg-transparent' : 'bg-[var(--md-sys-color-background)]'"
>
    <!-- 1. Dynamic Background Layer -->
    <div
        x-cloak
        class="absolute inset-0 -z-10 pointer-events-none overflow-hidden"
        x-show="$store.background.enabled"
        x-transition:enter="transition ease-out duration-1000"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-500"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <template x-for="i in $store.background.images.length" :key="i">
            <div
                class="absolute inset-0 bg-cover bg-center transition-transform duration-700 ease-[cubic-bezier(0.4,0.0,0.2,1)]"
                :class="getClasses(i - 1)"
                :style="{ backgroundImage: 'url(' + $store.background.images[i - 1] + ')' }">
            </div>
        </template>
        <div class="absolute inset-0 bg-[var(--md-sys-color-background)]/85 backdrop-blur-[1px]"></div>
    </div>

    <!-- 2. Pattern Background Layer -->
    <div class="absolute pointer-events-none h-full w-full animate-fade"
         x-show="$store.background.patternEnabled"
    >
        @for ($i = 0; $i < 3; $i++)
            <x-dashboard.shapes/>
        @endfor
    </div>

</div>
