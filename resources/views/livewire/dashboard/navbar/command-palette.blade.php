<div x-data="search()"
     @keydown.window.prevent.ctrl.k="toggle()"
     @keydown.window.prevent.cmd.k="toggle()"
     @keydown.escape.window="open = false"
     @open-command-palette.window="toggle()"
     class="relative z-[200]">

    <div x-cloak
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm"
         @click="open = false"></div>

    <div x-cloak
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="fixed inset-0 z-[201] flex items-start justify-center pt-[10vh] px-4 pointer-events-none">

        <div class="pointer-events-auto w-full max-w-2xl flex flex-col max-h-[60vh] rounded-2xl shadow-2xl overflow-hidden
                    bg-[var(--md-sys-color-surface)]
                    border border-[var(--md-sys-color-outline-variant)]/20
                    ring-1 ring-white/5
                    text-[var(--md-sys-color-on-surface)]">

            <div class="flex items-center gap-4 px-5 py-4 border-b border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-container)]/30 backdrop-blur-xl">
                <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)] animate-pulse">search</span>
                <input x-ref="searchInput"
                       wire:model.live.debounce.150ms="query"
                       type="text"
                       placeholder="جستجو در بخش‌های مختلف سیستم..."
                       class="w-full bg-transparent border-none outline-none text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-on-surface-variant)]/50 text-xl font-medium h-12"
                       @keydown.down.prevent="selectedIndex = Math.min(selectedIndex + 1, ($wire.query.length > 1 ? $wire.results.length : recentSearches.length) - 1)"
                       @keydown.up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                       @keydown.enter.prevent="
                           if ($wire.query.length > 1 && $wire.results.length > 0) {
                               $wire.selectResult($wire.results[selectedIndex].action);
                           } else if ($wire.query.length <= 1 && recentSearches.length > 0) {
                               selectHistoryItem(recentSearches[selectedIndex]);
                           }
                       ">
                <button @click="open = false" class="opacity-40 hover:opacity-100 px-2 py-1 bg-[var(--md-sys-color-surface-container-high)] rounded text-xs font-mono transition-opacity border border-[var(--md-sys-color-outline)]/20">ESC</button>
            </div>

            <div class="overflow-y-auto custom-scrollbar p-2 space-y-1 bg-[var(--md-sys-color-surface)]/50" style="max-height: 50vh;">

                @if(strlen($query) > 1)
                    @if(count($results) > 0)
                        <div class="px-2 py-1.5 text-xs font-bold opacity-40 uppercase tracking-widest">نتایج جستجو</div>
                        @foreach($results as $index => $result)
                            <button class="w-full flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200 group text-right outline-none border border-transparent"
                                    :class="{ 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]': selectedIndex === {{ $index }}, 'hover:bg-[var(--md-sys-color-surface-container-high)]': selectedIndex !== {{ $index }} }"
                                    @mouseenter="selectedIndex = {{ $index }}"
                                    wire:click="selectResult('{{ $result['action'] }}')">

                                <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors shadow-sm"
                                     :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]': selectedIndex === {{ $index }}, 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)]': selectedIndex !== {{ $index }} }">
                                    <span class="material-symbols-rounded text-[24px]">{{ $result['icon'] }}</span>
                                </div>

                                <div class="flex flex-col items-start flex-1 min-w-0">
                                    <div class="flex items-center gap-2 w-full">
                                        <span class="text-sm font-bold truncate">{{ $result['title'] }}</span>
                                        @if(str_contains($result['action'], 'tab:'))
                                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-[var(--md-sys-color-surface-variant)]/50 border border-[var(--md-sys-color-outline)]/20 opacity-70 ml-auto whitespace-nowrap">TAB</span>
                                        @elseif(str_contains($result['action'], 'route:'))
                                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-[var(--md-sys-color-surface-variant)]/50 border border-[var(--md-sys-color-outline)]/20 opacity-70 ml-auto whitespace-nowrap">PAGE</span>
                                        @endif
                                    </div>
                                    <span class="text-xs opacity-60 truncate max-w-full block">{{ $result['subtitle'] }}</span>
                                </div>

                                <span class="material-symbols-rounded text-[20px] transition-transform duration-200"
                                      :class="{ 'opacity-100 -translate-x-1 rtl:translate-x-1': selectedIndex === {{ $index }}, 'opacity-0 translate-x-2 rtl:-translate-x-2': selectedIndex !== {{ $index }} }">keyboard_return</span>
                            </button>
                        @endforeach
                    @else
                        <div class="flex flex-col items-center justify-center py-16 opacity-40">
                            <span class="material-symbols-rounded text-[64px] mb-4 text-[var(--md-sys-color-outline)]">search_off</span>
                            <p class="text-lg font-medium">نتیجه‌ای یافت نشد</p>
                            <p class="text-sm opacity-70">لطفاً عبارت دیگری را امتحان کنید</p>
                        </div>
                    @endif

                @else
                    <div x-cloak x-show="recentSearches.length > 0">
                        <div class="flex items-center justify-between px-2 py-1.5 mb-1">
                            <span class="text-xs font-bold opacity-40 uppercase tracking-widest">جستجوهای اخیر</span>
                            <button @click.stop="clearHistory()" class="text-[10px] opacity-40 hover:opacity-100 hover:text-red-400 transition-colors cursor-pointer">پاک کردن تاریخچه</button>
                        </div>

                        <template x-for="(item, index) in recentSearches" :key="index">
                            <button class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 group text-right outline-none border border-transparent mb-1"
                                    :class="{ 'bg-[var(--md-sys-color-surface-container-high)]': selectedIndex === index, 'hover:bg-[var(--md-sys-color-surface-container)]': selectedIndex !== index }"
                                    @mouseenter="selectedIndex = index"
                                    @click="selectHistoryItem(item)">

                                <span class="material-symbols-rounded text-[20px] opacity-40 group-hover:text-[var(--md-sys-color-primary)] transition-colors">history</span>

                                <div class="flex flex-col items-start flex-1 min-w-0">
                                    <span class="text-sm font-medium opacity-80 group-hover:opacity-100" x-text="item.title"></span>
                                    <span class="text-[10px] opacity-40 truncate" x-text="item.subtitle"></span>
                                </div>

                                <span class="material-symbols-rounded text-[16px] opacity-0 group-hover:opacity-50 -translate-x-2 group-hover:translate-x-0 rtl:translate-x-2 rtl:group-hover:translate-x-0 transition-all">chevron_left</span>
                            </button>
                        </template>
                    </div>

                    <div x-cloak x-show="recentSearches.length === 0" class="px-4 py-4">
                        <div class="text-center opacity-40 py-8">
                            <span class="material-symbols-rounded text-[48px] mb-2 block mx-auto">manage_search</span>
                            <p class="text-sm">برای شروع جستجو تایپ کنید...</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-[var(--md-sys-color-surface-container)]/50 px-4 py-2.5 border-t border-[var(--md-sys-color-outline-variant)]/10 flex items-center justify-between text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1.5"><kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/10 px-1.5 py-0.5 rounded shadow-sm min-w-[24px] text-center font-sans">Enter</kbd> انتخاب</span>
                    <span class="flex items-center gap-1.5"><kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/10 px-1.5 py-0.5 rounded shadow-sm min-w-[24px] text-center font-sans">↓</kbd> <kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/10 px-1.5 py-0.5 rounded shadow-sm min-w-[24px] text-center font-sans">↑</kbd> پیمایش</span>
                    <span class="flex items-center gap-1.5"><kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/10 px-1.5 py-0.5 rounded shadow-sm min-w-[24px] text-center font-sans">Esc</kbd> بستن</span>
                </div>
                <span class="opacity-50">Enterprise Navigation</span>
            </div>
        </div>
    </div>
</div>
