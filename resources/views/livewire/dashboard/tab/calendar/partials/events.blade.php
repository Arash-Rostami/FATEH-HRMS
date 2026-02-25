<div class="h-full flex flex-col p-4 md:p-6 overflow-hidden">
    <div class="flex items-center justify-between mb-5 shrink-0">
        <h3 class="text-lg md:text-xl font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">event_note</span>
            {{ formatJalaliDate($selectedDate) }}
        </h3>
        <span class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container-high)] px-3 py-1.5 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30">
            {{ count($this->selectedDayEvents) }}
        </span>
    </div>

    <div class="flex-1 overflow-y-auto space-y-3 pr-1 -mr-2 scrollbar-hide hover:scrollbar-default">
        @forelse($this->selectedDayEvents as $event)
            <div
                wire:key="event-{{ $event['id'] }}"
                class="group relative bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] rounded-[1.25rem] p-4 transition-all duration-200 border border-[var(--md-sys-color-outline-variant)]/30 hover:shadow-md hover:border-[var(--md-sys-color-primary)]/30"
            >
                <div class="flex gap-4">
                    <div class="shrink-0 relative">
                        @if(!empty($event['avatar']))
                            <img src="{{ $event['avatar'] }}" class="w-12 h-12 rounded-[1rem] object-cover ring-2 ring-[var(--md-sys-color-surface-variant)]">
                            @if(($event['type'] ?? '') === 'birthday')
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-pink-100 text-pink-600 rounded-xl flex items-center justify-center border border-white shadow-sm">
                                    <span class="material-symbols-rounded text-[12px]" style="font-variation-settings: 'FILL' 1;">cake</span>
                                </div>
                            @elseif(($event['type'] ?? '') === 'anniversary')
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center border border-white shadow-sm">
                                    <span class="material-symbols-rounded text-[12px]" style="font-variation-settings: 'FILL' 1;">celebration</span>
                                </div>
                            @endif
                        @else
                            <div class="w-12 h-12 rounded-[1rem] {{ getEventStyles($event['type'] ?? '') }} flex items-center justify-center transition-colors">
                                @if(($event['type'] ?? '') === 'birthday')
                                    <span class="material-symbols-rounded" style="font-variation-settings: 'FILL' 1;">cake</span>
                                @elseif(($event['type'] ?? '') === 'anniversary')
                                    <span class="material-symbols-rounded" style="font-variation-settings: 'FILL' 1;">celebration</span>
                                @else
                                    <span class="material-symbols-rounded">event</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0 pt-0.5">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="font-bold text-[var(--md-sys-color-on-surface)] truncate text-sm md:text-base">
                                {{ $event['title'] }}
                            </h4>
                            <span class="text-[10px] font-bold font-mono text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]/50 px-2 py-1 rounded-lg shrink-0 ml-2">
                                {{ $event['time'] }}
                            </span>
                        </div>

                        <p class="text-xs md:text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed opacity-90">
                            {{ $event['description'] }}
                        </p>

                        <div class="flex items-center gap-3 mt-3">
                            <div class="flex items-center gap-1 text-[10px] text-[var(--md-sys-color-outline)] bg-[var(--md-sys-color-surface-container-high)] px-2 py-0.5 rounded-md">
                                @if($event['private'])
                                    <span class="material-symbols-rounded text-[12px]">lock</span>
                                    <span>خصوصی</span>
                                @else
                                    <span class="material-symbols-rounded text-[12px]">public</span>
                                    <span>عمومی</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($event['is_owner'])
                    <div class="absolute bottom-1 left-3 flex gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-x-2 group-hover:translate-x-0">
                        <button
                            wire:click="editEvent({{ $event['id'] }})"
                            class="px-2 py-1 bg-[var(--md-sys-color-surface-container-highest)] rounded-xl text-[var(--md-sys-color-primary)] shadow-sm hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-[16px]">edit</span>
                        </button>
                        <button
                            wire:click="confirmDelete({{ $event['id'] }})"
                            class="px-2 py-1 bg-[var(--md-sys-color-error-container)] rounded-xl text-[var(--md-sys-color-on-error-container)] shadow-sm hover:bg-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-[16px]">delete</span>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="h-full flex flex-col items-center justify-center text-center opacity-60 pb-10">
                <div class="w-20 h-20 bg-[var(--md-sys-color-surface-variant)]/30 rounded-[2rem] flex items-center justify-center mb-4 rotate-3 shadow-inner">
                    <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-outline)]">calendar_today</span>
                </div>
                <p class="text-[var(--md-sys-color-on-surface)] font-bold text-lg">رویدادی یافت نشد</p>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mt-1 max-w-[200px]">
                    برای این روز هنوز هیچ برنامه ای ثبت نکرده‌اید.
                </p>
            </div>
        @endforelse
    </div>
</div>
