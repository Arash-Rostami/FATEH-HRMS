<div x-data="background"
     class="fixed inset-0 w-full h-full pointer-events-none transition-colors duration-500 overflow-hidden"
     :class="{
         'bg-transparent': $store.background.enabled || $store.background.patternEnabled,
         'bg-[var(--md-sys-color-background)]': !($store.background.enabled || $store.background.patternEnabled)
     }"
     style="z-index: -1;"
>
    <div
        class="absolute inset-x-0 bottom-0 -z-10 pointer-events-none overflow-hidden"
        :class="$store.background.fit === 'top' ? 'top-[124px] lg:top-[144px]' : 'top-0'"
        x-show="$store.background.enabled"
        x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-600"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-350"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
    >
        <template x-for="i in renderIndices" :key="i">
            <div
                class="absolute inset-0 bg-cover"
                :class="[getClasses(i), $store.background.fit === 'top' ? 'bg-top' : 'bg-center']"
                :style="imageStyle(i)">
            </div>
        </template>
        <div class="absolute inset-0 bg-[var(--md-sys-color-background)]" :style="{ opacity: $store.background.opacity }"></div>
    </div>
</div>
