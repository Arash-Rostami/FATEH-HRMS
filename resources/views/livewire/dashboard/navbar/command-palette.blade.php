<div x-data="palette"
     @keydown.window.prevent.ctrl.k="toggle()"
     @keydown.window.prevent.cmd.k="toggle()"
     @keydown.escape.window="open = false"
     class="relative z-[200]">

    <button @click="toggle()"
            class="hidden md:flex items-center gap-3 px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)]/50 border border-[var(--md-sys-color-outline-variant)]/20 hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors group">
        <span class="material-symbols-rounded text-[20px] opacity-50 group-hover:opacity-100 transition-opacity">search</span>
        <span class="text-xs opacity-30 group-hover:opacity-50 transition-opacity">جستجو...</span>
        <div class="flex items-center gap-1 mr-4">
            <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface)]/20 border border-[var(--md-sys-color-outline-variant)]/20 text-[10px] opacity-30 font-mono">Ctrl</span>
            <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface)]/20 border border-[var(--md-sys-color-outline-variant)]/20 text-[10px] opacity-30 font-mono">K</span>
        </div>
    </button>

    <button @click="toggle()"
            class="md:hidden w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)] active:bg-[var(--md-sys-color-surface-container-highest)] flex items-center justify-center opacity-70">
        <span class="material-symbols-rounded text-[22px]">search</span>
    </button>

    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm"
         @click="open = false"
         style="display: none;"></div>

    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="fixed inset-0 z-[201] flex items-start justify-center pt-[10vh] px-4"
         style="display: none;">

        <div class="w-full max-w-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/10 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[60vh] text-[var(--md-sys-color-on-surface)]">

            <div class="flex items-center gap-3 px-4 py-4 border-b border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-container)]/30">
                <span class="material-symbols-rounded text-[24px] text-emerald-400 animate-pulse">search</span>
                <input x-ref="searchInput"
                       wire:model.live="query"
                       type="text"
                       placeholder="جستجو در بخش‌های مختلف سیستم..."
                       class="w-full bg-transparent border-none outline-none text-current placeholder-current/30 text-lg font-medium h-10"
                       @keydown.down.prevent="selectedIndex = Math.min(selectedIndex + 1, $wire.results.length - 1)"
                       @keydown.up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                       @keydown.enter.prevent="if($wire.results.length > 0) { alert('Action: ' + $wire.results[selectedIndex].action); open = false; }">
                <button @click="open = false" class="opacity-30 hover:opacity-100 px-2 py-1 bg-[var(--md-sys-color-surface-container)] rounded text-xs">ESC</button>
            </div>

            <div class="overflow-y-auto custom-scrollbar p-2 space-y-1">
                @if(count($results) > 0)
                    @foreach($results as $index => $result)
                        <button class="w-full flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200 group text-right hover:bg-[var(--md-sys-color-surface-container-high)] focus:bg-[var(--md-sys-color-surface-container-high)] outline-none border border-transparent focus:border-emerald-500/50"
                                :class="{ 'bg-[var(--md-sys-color-surface-container-high)] ring-1 ring-emerald-500/50': selectedIndex === {{ $index }} }"
                                @mouseenter="selectedIndex = {{ $index }}">
                            <div class="w-10 h-10 rounded-lg bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/10 flex items-center justify-center group-hover:bg-emerald-500/20 group-hover:border-emerald-500/50 transition-colors">
                                <span class="material-symbols-rounded text-[24px] opacity-70 group-hover:text-emerald-400">{{ $result['icon'] }}</span>
                            </div>
                            <div class="flex flex-col items-start flex-1">
                                <span class="text-sm font-medium group-hover:text-emerald-500">{{ $result['title'] }}</span>
                                <span class="text-xs opacity-40 group-hover:opacity-60">{{ $result['subtitle'] }}</span>
                            </div>
                            <span class="material-symbols-rounded text-[18px] opacity-0 group-hover:opacity-100 group-hover:text-emerald-400 transition-all -translate-x-2 group-hover:translate-x-0">chevron_left</span>
                        </button>
                    @endforeach
                @elseif(strlen($query) > 1)
                    <div class="flex flex-col items-center justify-center py-12 opacity-30">
                        <span class="material-symbols-rounded text-[48px] mb-2 opacity-50">search_off</span>
                        <p class="text-sm">نتیجه‌ای یافت نشد</p>
                    </div>
                @else
                    <div class="px-4 py-2">
                        <h4 class="text-xs font-bold opacity-30 uppercase tracking-wider mb-2">پیشنهادات سریع</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="flex items-center gap-2 p-3 rounded-xl bg-[var(--md-sys-color-surface-container)]/50 hover:bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/10 hover:border-emerald-500/30 transition-all group text-right">
                                <span class="material-symbols-rounded opacity-50 group-hover:text-emerald-400">calendar_month</span>
                                <span class="text-xs opacity-70 group-hover:opacity-100">تقویم کاری</span>
                            </button>
                            <button class="flex items-center gap-2 p-3 rounded-xl bg-[var(--md-sys-color-surface-container)]/50 hover:bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/10 hover:border-sky-500/30 transition-all group text-right">
                                <span class="material-symbols-rounded opacity-50 group-hover:text-sky-400">mail</span>
                                <span class="text-xs opacity-70 group-hover:opacity-100">پیام‌ها</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-[var(--md-sys-color-surface-container)]/30 px-4 py-2 border-t border-[var(--md-sys-color-outline-variant)]/10 flex items-center justify-between text-[10px] opacity-30">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1"><kbd class="bg-[var(--md-sys-color-surface-container)] px-1 rounded">Enter</kbd> انتخاب</span>
                    <span class="flex items-center gap-1"><kbd class="bg-[var(--md-sys-color-surface-container)] px-1 rounded">↓</kbd> <kbd class="bg-[var(--md-sys-color-surface-container)] px-1 rounded">↑</kbd> پیمایش</span>
                </div>
                <span>AI Search v1.0</span>
            </div>
        </div>
    </div>
</div>
