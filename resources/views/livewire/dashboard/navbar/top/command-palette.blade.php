<div x-data="search()"
     @keydown.window.prevent.ctrl.k="toggle()"
     @keydown.window.prevent.cmd.k="toggle()"
     @keydown.escape.window="open = false"
     @open-command-palette.window="toggle()"
     class="relative z-[200]">

    {{-- Backdrop --}}
    <div x-cloak
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-[var(--md-sys-color-primary)]/60"
         @click="open = false">
    </div>

    {{-- Modal Container --}}
    <div x-cloak
         x-show="open"
         class="fixed inset-0 z-[201] flex items-start justify-center pt-[8vh] px-4 pb-4 pointer-events-none animate-slide-down">

        <div class="pointer-events-auto w-full max-w-3xl flex flex-col h-[75vh] rounded-[28px] shadow-2xl overflow-hidden bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 ring-1 ring-white/5 text-[var(--md-sys-color-on-surface)]">

            {{-- Header / Search Bar --}}
            <div class="flex items-center gap-4 px-6 py-5 border-b border-[var(--md-sys-color-outline-variant)]/15 bg-[var(--md-sys-color-surface-container)]/40">
                <span class="material-symbols-rounded text-[32px] text-[var(--md-sys-color-primary)] transition-all duration-300">
                    {{ $mode === 'content' ? 'travel_explore' : 'search' }}
                </span>

                <input x-ref="searchInput"
                       wire:model.live.debounce.{{ $mode === 'content' ? '300' : '150' }}ms="query"
                       type="text"
                       placeholder="{{ $mode === 'content' ? 'جستجوی محتوا در ۶ ماه اخیر (اسناد، گزارش‌ها، تیکت‌ها…)' : 'جستجو در بخش‌های مختلف سیستم...' }}"
                       class="w-full bg-transparent border-none outline-none text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-on-surface-variant)]/40 text-xl font-medium h-12"
                       @keydown.down.prevent="selectedIndex = Math.min(selectedIndex + 1, ($wire.query.length > 1 ? $wire.results.length : recentSearches.length) - 1)"
                       @keydown.up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                       @keydown.enter.prevent="
                           if ($wire.query.length > 1 && !resultsFresh) {
                               // debounced request still in flight, results are stale — ignore this Enter
                           } else if ($wire.query.length > 1 && $wire.results.length > 0 && $wire.results[selectedIndex]) {
                               $wire.selectResult($wire.results[selectedIndex].action);
                           } else if ($wire.query.length <= 1 && recentSearches.length > 0 && recentSearches[selectedIndex]) {
                               selectHistoryItem(recentSearches[selectedIndex]);
                           }
                       ">

                {{-- Mode Toggle --}}
                <button wire:click="toggleMode"
                        @click="selectedIndex = 0; $nextTick(() => $refs.searchInput.focus())"
                        type="button"
                        :title="'{{ $mode === 'content' ? 'تغییر به ناوبری سریع' : 'تغییر به جستجوی محتوا' }}'"
                        class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold transition-all border whitespace-nowrap bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)]/20 hover:text-[var(--md-sys-color-primary)] hover:border-[var(--md-sys-color-primary)]/30 hover:shadow-sm hover:-translate-y-0.5">
                    <span class="material-symbols-rounded text-[20px]">{{ $mode === 'content' ? 'bolt' : 'manage_search' }}</span>
                    <span>{{ $mode === 'content' ? 'ناوبری' : 'محتوا' }}</span>
                </button>

                <button @click="open = false"
                        class="opacity-40 hover:opacity-100 hover:bg-[var(--md-sys-color-surface-container-highest)] px-2.5 py-1.5 bg-[var(--md-sys-color-surface-container-high)] rounded-lg text-xs font-mono transition-all border border-[var(--md-sys-color-outline)]/20 hover:shadow-sm">
                    ESC
                </button>
            </div>

            {{-- Results Area --}}
            <div class="overflow-y-auto custom-scrollbar p-3 space-y-1 bg-[var(--md-sys-color-surface)]/50 flex-1">

                @if($mode === 'content' && strlen($query) > 1 && !$allHistory && count($results) <= 5)
                    <div wire:key="palette-search-all-history" class="flex justify-center pt-1 pb-1">
                        <button wire:click="searchAllHistory" wire:loading.attr="disabled"
                                @click="selectedIndex = 0"
                                type="button"
                                class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[11px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline)]/20 hover:text-[var(--md-sys-color-primary)] hover:border-[var(--md-sys-color-primary)]/40 transition-all">
                            <span class="material-symbols-rounded text-[16px]">history</span>
                            جستجو در کل تاریخ (فراتر از ۶ ماه اخیر)
                        </button>
                    </div>
                @endif

                @if($mode === 'content' && $allHistory)
                    <div wire:key="palette-all-history" class="flex justify-center pt-1 pb-1">
                        <span class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[11px] font-bold bg-[var(--md-sys-color-primary-container)]/50 text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-primary)]/20">
                            <span class="material-symbols-rounded text-[16px]">all_inclusive</span>
                            جستجو در کل تاریخ فعال است
                        </span>
                    </div>
                @endif

                @if(strlen($query) > 1)
                    @if(count($results) > 0)

                        @if($mode === 'content')
                            <div wire:key="palette-results-content" class="contents">
                            {{-- Content Mode Results --}}
                            @php $flatIndex = 0; @endphp
                            @foreach(collect($results)->groupBy('group') as $groupName => $groupItems)

                                <div class="sticky top-0 z-10 flex items-center justify-between px-3 py-2 mt-4 first:mt-0 mb-2 bg-[var(--md-sys-color-surface)]/95 border-b border-[var(--md-sys-color-outline-variant)]/10 rounded-t-lg">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-rounded text-[18px] opacity-50">{{ $groupItems->first()['icon'] }}</span>
                                        <span class="text-xs font-bold opacity-60 uppercase tracking-widest">{{ $groupName }}</span>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/20 opacity-70">{{ convertToPersian($groupItems->count()) }}</span>
                                </div>

                                @foreach($groupItems as $result)
                                    @php $index = $flatIndex++; @endphp
                                    <button class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-200 group text-right outline-none border border-transparent relative overflow-hidden mb-1"
                                            :class="{ 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] shadow-sm': selectedIndex === {{ $index }}, 'hover:bg-[var(--md-sys-color-surface-container-low)]': selectedIndex !== {{ $index }} }"
                                            @mouseenter="selectedIndex = {{ $index }}"
                                            wire:click="selectResult('{{ $result['action'] }}')">

                                        <div class="absolute inset-y-3 start-0 w-1.5 rounded-e-full transition-all duration-300"
                                             :class="{ 'bg-[var(--md-sys-color-primary)] scale-y-100': selectedIndex === {{ $index }}, 'bg-transparent scale-y-0': selectedIndex !== {{ $index }} }"></div>

                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 shrink-0"
                                             :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md scale-105': selectedIndex === {{ $index }}, 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)]': selectedIndex !== {{ $index }} }">
                                            <span class="material-symbols-rounded text-[26px]">{{ $result['icon'] }}</span>
                                        </div>

                                        <div class="flex flex-col items-start flex-1 min-w-0 py-1">
                                            <span class="text-sm font-bold truncate max-w-full block transition-colors"
                                                  :class="{ 'text-[var(--md-sys-color-on-secondary-container)]': selectedIndex === {{ $index }} }">
                                                {{ superClean($result['title'] ?? '', 80) }}
                                            </span>
                                            <span class="text-xs opacity-70 truncate max-w-full block mt-0.5">
                                                {{ superClean($result['subtitle'] ?? '', 120) }}
                                            </span>

                                            @if($result['type'] === 'task')
                                                @php($urgencyKind = $result['urgency_state']['kind'] ?? null)
                                                <div class="flex items-center gap-2 mt-1">
                                                    @if(in_array($urgencyKind, ['overdue', 'due'], true))
                                                        <span class="inline-flex h-1.5 w-1.5 rounded-full {{ $urgencyKind === 'overdue' ? 'bg-[var(--md-sys-color-error)] animate-pulse-ring' : 'bg-[var(--tool-gold-text)]' }}" title="{{ $result['urgency_state']['label'] ?? '' }}"></span>
                                                    @elseif($urgencyKind === 'idle')
                                                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-[var(--md-sys-color-on-surface-variant)]/40" title="{{ $result['urgency_state']['label'] ?? '' }}"></span>
                                                    @endif

                                                    <x-ui.decor.progress-ring :percent="$result['progress_percent']" :size="18" :stroke="2"
                                                                              :color="$result['progress_percent'] >= 100 ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)'"/>

                                                    @if(!empty($result['collaborator_avatars']))
                                                        <div class="flex items-center -space-x-1.5 rtl:space-x-reverse shrink-0" title="همکاران: {{ implode('، ', array_column($result['collaborator_avatars'], 'name')) }}">
                                                            @foreach(array_slice($result['collaborator_avatars'], 0, 3) as $collaborator)
                                                                <img src="{{ $collaborator['avatar_url'] }}" alt="{{ $collaborator['name'] }}"
                                                                     class="w-4 h-4 rounded-full border border-[var(--md-sys-color-surface)] object-cover ring-1 ring-[var(--md-sys-color-outline-variant)]">
                                                            @endforeach
                                                            @if(count($result['collaborator_avatars']) > 3)
                                                                <span class="w-4 h-4 rounded-full bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] text-[8px] font-bold flex items-center justify-center border border-[var(--md-sys-color-surface)] ring-1 ring-[var(--md-sys-color-outline-variant)]">
                                                                    +{{ convertToPersian(count($result['collaborator_avatars']) - 3) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <span class="material-symbols-rounded text-[22px] transition-all duration-300"
                                              :class="{ 'opacity-100 -translate-x-1 rtl:translate-x-1 text-[var(--md-sys-color-primary)]': selectedIndex === {{ $index }}, 'opacity-0 translate-x-4 rtl:-translate-x-4': selectedIndex !== {{ $index }} }">
                                            keyboard_return
                                        </span>
                                    </button>
                                @endforeach
                            @endforeach
                            </div>
                        @else
                            <div wire:key="palette-results-nav" class="contents">
                            {{-- Navigation Mode Results --}}
                            <div class="px-3 py-2 text-xs font-bold opacity-50 uppercase tracking-widest mb-1 border-b border-[var(--md-sys-color-outline-variant)]/10">
                                نتایج جستجو
                            </div>
                            @foreach($results as $index => $result)
                                <button class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-200 group text-right outline-none border border-transparent relative overflow-hidden mb-1"
                                        :class="{ 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] shadow-sm': selectedIndex === {{ $index }}, 'hover:bg-[var(--md-sys-color-surface-container-low)]': selectedIndex !== {{ $index }} }"
                                        @mouseenter="selectedIndex = {{ $index }}"
                                        wire:click="selectResult('{{ $result['action'] }}')">

                                    <div class="absolute inset-y-3 start-0 w-1.5 rounded-e-full transition-all duration-300"
                                         :class="{ 'bg-[var(--md-sys-color-primary)] scale-y-100': selectedIndex === {{ $index }}, 'bg-transparent scale-y-0': selectedIndex !== {{ $index }} }"></div>

                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 shrink-0"
                                         :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md scale-105': selectedIndex === {{ $index }}, 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)]': selectedIndex !== {{ $index }} }">
                                        <span class="material-symbols-rounded text-[26px]">{{ $result['icon'] }}</span>
                                    </div>

                                    <div class="flex flex-col items-start flex-1 min-w-0 py-1">
                                        <div class="flex items-center gap-2 w-full">
                                            <span class="text-sm font-bold truncate transition-colors"
                                                  :class="{ 'text-[var(--md-sys-color-on-secondary-container)]': selectedIndex === {{ $index }} }">
                                                {{ superClean($result['title'] ?? '', 80) }}
                                            </span>
                                            @if(str_contains($result['action'], 'tab:'))
                                                <span class="text-[9px] px-2 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)]/60 border border-[var(--md-sys-color-outline)]/20 font-bold opacity-80 ml-auto whitespace-nowrap">TAB</span>
                                            @elseif(str_contains($result['action'], 'route:'))
                                                <span class="text-[9px] px-2 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)]/60 border border-[var(--md-sys-color-outline)]/20 font-bold opacity-80 ml-auto whitespace-nowrap">PAGE</span>
                                            @endif
                                        </div>
                                        <span class="text-xs opacity-70 truncate max-w-full block mt-0.5">
                                            {{ superClean($result['subtitle'] ?? '', 120) }}
                                        </span>
                                    </div>

                                    <span class="material-symbols-rounded text-[22px] transition-all duration-300"
                                          :class="{ 'opacity-100 -translate-x-1 rtl:translate-x-1 text-[var(--md-sys-color-primary)]': selectedIndex === {{ $index }}, 'opacity-0 translate-x-4 rtl:-translate-x-4': selectedIndex !== {{ $index }} }">
                                        keyboard_return
                                    </span>
                                </button>
                            @endforeach
                            </div>
                        @endif

                    @else
                        <div wire:key="palette-results-empty">
                            <x-ui.empty icon="search_off"
                                        title="نتیجه‌ای یافت نشد"
                                        description="{{ $mode === 'content' && !$allHistory ? 'در ۶ ماه اخیر نتیجه‌ای نبود؛ دکمهٔ «جستجو در کل تاریخ» را بزنید' : 'لطفاً عبارت دیگری را امتحان کنید' }}"
                                        variant="search" />
                        </div>
                    @endif

                @else
                    {{-- Recent Searches --}}
                    <div wire:key="palette-recent-searches" x-cloak x-show="recentSearches.length > 0" class="pt-2">
                        <div class="flex items-center justify-between px-3 py-2 mb-2 border-b border-[var(--md-sys-color-outline-variant)]/10">
                            <span class="text-xs font-bold opacity-50 uppercase tracking-widest">جستجوهای اخیر</span>
                            <button @click.stop="clearHistory()"
                                    class="text-[10px] font-bold opacity-50 hover:opacity-100 hover:text-[var(--md-sys-color-error)] transition-colors cursor-pointer px-2 py-1 rounded-md hover:bg-[var(--md-sys-color-error-container)]/20">
                                پاک کردن تاریخچه
                            </button>
                        </div>

                        <template x-for="(item, index) in recentSearches" :key="index">
                            <button class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-200 group text-right outline-none border border-transparent mb-1"
                                    :class="{ 'bg-[var(--md-sys-color-surface-container-high)] shadow-sm': selectedIndex === index, 'hover:bg-[var(--md-sys-color-surface-container)]': selectedIndex !== index }"
                                    @mouseenter="selectedIndex = index"
                                    @click="selectHistoryItem(item)">

                                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-surface-container-highest)]/50 group-hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors shrink-0">
                                    <span class="material-symbols-rounded text-[20px] opacity-60 group-hover:text-[var(--md-sys-color-primary)] group-hover:opacity-100 transition-colors">history</span>
                                </div>

                                <div class="flex flex-col items-start flex-1 min-w-0">
                                    <span class="text-sm font-bold opacity-80 group-hover:opacity-100 transition-opacity"
                                          x-text="item.title ? item.title.replace(/<[^>]*>?/gm, '') : ''"></span>
                                    <span class="text-[11px] opacity-50 truncate mt-0.5"
                                          x-text="item.subtitle ? item.subtitle.replace(/<[^>]*>?/gm, '') : ''"></span>
                                </div>

                                <span class="material-symbols-rounded text-[20px] opacity-0 group-hover:opacity-60 -translate-x-4 group-hover:-translate-x-1 rtl:translate-x-4 rtl:group-hover:translate-x-1 transition-all duration-300">
                                    chevron_left
                                </span>
                            </button>
                        </template>
                    </div>

                    {{-- Initial Idle State --}}
                    <div wire:key="palette-idle" x-cloak x-show="recentSearches.length === 0"
                         class="px-4 py-4 h-full flex flex-col items-center justify-center">
                        <div class="text-center opacity-40 py-16">
                            <div class="w-24 h-24 bg-[var(--md-sys-color-surface-container-high)] rounded-[32px] flex items-center justify-center mx-auto mb-6 rotate-3">
                                <span class="material-symbols-rounded text-[48px] -rotate-3">manage_search</span>
                            </div>
                            <p class="text-base font-medium">برای شروع جستجو تایپ کنید...</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Metrics & Footer --}}
            <div class="bg-[var(--md-sys-color-surface-container)]/60 px-6 py-3.5 border-t border-[var(--md-sys-color-outline-variant)]/15 flex items-center justify-between text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] rounded-b-[28px]">

                {{-- Keyboard Shortcuts --}}
                <div class="flex items-center gap-5">
                    <span class="flex items-center gap-2">
                        <kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/20 px-2 py-1 rounded-md shadow-sm min-w-[28px] text-center font-sans font-bold">Enter</kbd>
                        انتخاب
                    </span>
                    <span class="flex items-center gap-2">
                        <kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/20 px-2 py-1 rounded-md shadow-sm min-w-[28px] text-center font-sans font-bold">↓</kbd>
                        <kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/20 px-2 py-1 rounded-md shadow-sm min-w-[28px] text-center font-sans font-bold">↑</kbd>
                        پیمایش
                    </span>
                    <span class="flex items-center gap-2">
                        <kbd class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/20 px-2 py-1 rounded-md shadow-sm min-w-[28px] text-center font-sans font-bold">Esc</kbd>
                        بستن
                    </span>
                </div>

                {{-- System Metrics --}}
                <div class="flex items-center gap-2"
                     x-data="{ ttl: 0, start: performance.now() }"
                     x-init="$watch('$wire.query', () => { start = performance.now(); }); $watch('$wire.results', () => { ttl = Math.round(performance.now() - start); })">

                    <div class="flex items-center gap-1.5 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/15 px-2 py-1 rounded-md shadow-sm">
                        @if($mode === 'content')
                            <div wire:key="palette-mode-chip-content" class="contents">
                            <span class="material-symbols-rounded text-[14px] opacity-60">{{ $allHistory ? 'all_inclusive' : 'history' }}</span>
                            <span class="text-[9px] font-bold tracking-widest uppercase opacity-70">
                                {{ $allHistory ? 'کل تاریخ' : '۶ ماه اخیر' }}
                            </span>
                            </div>
                        @else
                            <div wire:key="palette-mode-chip-nav" class="contents">
                            <span class="material-symbols-rounded text-[14px] opacity-60">schema</span>
                            <span class="text-[9px] font-bold tracking-widest uppercase opacity-70">Graph</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-1.5 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/15 px-2 py-1 rounded-md shadow-sm">
                        <span class="material-symbols-rounded text-[14px] opacity-60">data_array</span>
                        <span class="text-[9px] font-bold tracking-widest uppercase opacity-70">
                            Yield: {{ convertToPersian(count($results ?? [])) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)]/15 px-2.5 py-1 rounded-md shadow-sm transition-colors duration-300"
                         :class="ttl > 500 ? 'border-[var(--md-sys-color-error)]/30 bg-[var(--md-sys-color-error-container)]/10' : ''">

                        <span class="relative flex h-1.5 w-1.5">
                            <span class="absolute inline-flex h-full w-full rounded-full opacity-75"
                                  :class="(ttl > 0 && ttl < 500 ? 'animate-ping bg-[var(--md-sys-color-primary)]' : (ttl > 500 ? 'bg-[var(--md-sys-color-error)]' : 'bg-[var(--md-sys-color-primary)]'))"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5"
                                  :class="ttl > 500 ? 'bg-[var(--md-sys-color-error)]' : 'bg-[var(--md-sys-color-primary)]'"></span>
                        </span>

                        <span class="text-[10px] font-bold tracking-widest uppercase"
                              :class="ttl > 500 ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-primary)] opacity-90'"
                              x-text="ttl > 0 ? ttl + 'ms' : 'READY'"></span>
                    </div>
                </div>
            </div>
            <div class="relative right-4/5 bottom-1 opacity-50 p-0 m-0">
                <small>powered by {{ config('app.name_en') }}✨</small>
            </div>
        </div>
    </div>
</div>
