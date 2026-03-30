<div x-data="background"
     class="fixed inset-0 w-full h-full pointer-events-none transition-colors duration-500 overflow-hidden"
     :class="{
         'bg-transparent': $store.background.enabled || $store.background.patternEnabled,
         'bg-[var(--md-sys-color-background)]': !($store.background.enabled || $store.background.patternEnabled)
     }"
     style="z-index: -1;"
>
    <div
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
        <div class="absolute inset-0 bg-[var(--md-sys-color-background)]/85 "></div>
    </div>
</div>
