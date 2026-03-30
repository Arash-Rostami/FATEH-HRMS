import re

with open('resources/views/livewire/dashboard/navbar/quick-settings.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

pattern_button = """            <button @click="togglePattern()"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group mb-1">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                         :class="patternEnabled ? 'bg-amber-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                        <span class="material-symbols-rounded text-[20px]">interests</span>
                    </div>
                    <div class="flex flex-col items-start text-right">
                        <span class="text-sm font-medium group-hover:text-amber-500">پس‌زمینه طرح دار</span>
                        <span class="text-[10px] opacity-50 group-hover:opacity-70">نمایش اشکال هندسی</span>
                    </div>
                </div>
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                     :class="patternEnabled ? 'bg-amber-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                         :class="patternEnabled ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>"""

new_pattern_ui = """            <div class="mb-1">
                <button @click="togglePattern()"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                             :class="patternEnabled ? 'bg-amber-500 text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
                            <span class="material-symbols-rounded text-[20px]">interests</span>
                        </div>
                        <div class="flex flex-col items-start text-right">
                            <span class="text-sm font-medium group-hover:text-amber-500">پس‌زمینه طرح دار</span>
                            <span class="text-[10px] opacity-50 group-hover:opacity-70">نمایش اشکال هندسی</span>
                        </div>
                    </div>
                    <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
                         :class="patternEnabled ? 'bg-amber-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                        <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
                             :class="patternEnabled ? 'translate-x-4' : 'translate-x-0'"></div>
                    </div>
                </button>

                <!-- Pattern Selection List (Shows only when pattern is enabled) -->
                <div x-show="patternEnabled"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-2 ml-2 pl-4 pr-2 border-r-2 border-amber-500/30 space-y-1">
                    <template x-for="pattern in availablePatterns" :key="pattern.id">
                        <label class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]"
                               :class="activePattern === pattern.id ? 'bg-[var(--md-sys-color-surface-container-highest)]' : ''">
                            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface)]" x-text="pattern.name"></span>
                            <input type="radio"
                                   name="pattern_selection"
                                   :value="pattern.id"
                                   x-model="activePattern"
                                   @change="setPattern(pattern.id)"
                                   class="w-3.5 h-3.5 text-amber-500 focus:ring-amber-500/50 bg-[var(--md-sys-color-surface-container)] border-[var(--md-sys-color-outline-variant)]">
                        </label>
                    </template>
                </div>
            </div>"""

content = content.replace(pattern_button, new_pattern_ui)

with open('resources/views/livewire/dashboard/navbar/quick-settings.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
