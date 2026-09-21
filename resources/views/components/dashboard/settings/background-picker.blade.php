<div>
    <x-dashboard.settings.toggle-row
        icon="wallpaper"
        label="پس‌زمینه پویا"
        color="teal"
        active="$store.background.enabled"
        action="toggleBackground()"
    />

    <div class="mb-1">
        <x-dashboard.settings.toggle-row
            icon="interests"
            label="پس‌زمینه طرح دار"
            color="amber"
            active="$store.background.patternEnabled"
            action="togglePattern()"
        />

        <div x-show="$store.background.patternEnabled"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-2 border-r-2 border-amber-500/30 mr-4 pr-3">
            <div class="space-y-1 max-h-40 overflow-y-auto overscroll-contain pl-1
                        [&::-webkit-scrollbar]:w-1.5
                        [&::-webkit-scrollbar-track]:bg-transparent
                        [&::-webkit-scrollbar-thumb]:bg-[var(--md-sys-color-outline-variant)]/30
                        [&::-webkit-scrollbar-thumb]:rounded-full
                        hover:[&::-webkit-scrollbar-thumb]:bg-[var(--md-sys-color-outline-variant)]/50
                        [scrollbar-width:thin]">
                <template x-for="pattern in availablePatterns" :key="pattern.id">
                    <div @click="setPattern(pattern.id)"
                         class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]"
                         :class="$store.background.activePattern === pattern.id ? 'bg-[var(--md-sys-color-surface-container-highest)]' : ''">
                        <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface)]" x-text="pattern.name"></span>
                        <div class="relative w-4 h-4 rounded-full border-2 transition-colors flex items-center justify-center"
                             :class="$store.background.activePattern === pattern.id ? 'border-amber-500' : 'border-[var(--md-sys-color-outline-variant)]'">
                            <div class="w-2 h-2 rounded-full transition-transform duration-200"
                                 :class="$store.background.activePattern === pattern.id ? 'bg-amber-500 scale-100' : 'bg-transparent scale-0'">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
