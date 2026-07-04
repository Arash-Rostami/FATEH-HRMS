<div class="h-full flex flex-col p-4 md:p-6 overflow-hidden bg-[var(--md-sys-color-surface)]">
    @php $d = $this->activeDate; @endphp
    <div class="flex items-center justify-between mb-5 shrink-0 gap-4">
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="relative flex items-center justify-center shrink-0">
                <span class="material-symbols-rounded text-[32px] text-[var(--md-sys-color-primary)]">calendar_month</span>
            </div>
            <div class="flex flex-col justify-center min-w-0 gap-0.5">
                <div class="flex items-baseline gap-2">
                    <h3 class="text-xl md:text-2xl font-black text-[var(--md-sys-color-on-surface)] tracking-tight truncate">
                        {{ convertToPersian($d['jalali']) }}
                    </h3>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]/80">public</span>
                    <span dir="ltr" class="text-xs font-semibold text-[var(--md-sys-color-on-surface-variant)]/90 tabular-nums tracking-widest uppercase mt-0.5">
                    {{ $d['gregorian'] }}
                </span>
                </div>
            </div>
        </div>
        <div class="flex flex-col items-center justify-center min-w-[3rem] px-3.5 py-2 bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl shrink-0">
        <span class="text-base font-black text-[var(--md-sys-color-primary)] leading-none">
            {{ count($this->selectedDayEvents) }}
        </span>
        </div>
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
                            <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]/50 px-2 py-1 rounded-lg shrink-0 ml-2">
                                {{ convertToPersian($event['time']) }}
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

                            @if(!empty($event['is_shared']))
                                <div class="flex items-center gap-1 text-[10px] text-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary-container)]/50 px-2 py-0.5 rounded-md">
                                    <span class="material-symbols-rounded text-[12px]">group</span>
                                    <span>مشترک</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($event['is_owner'])
                    <div class="absolute bottom-1 left-3 flex gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-x-2 group-hover:translate-x-0">
                        <button
                            wire:click="openShareModal({{ $event['id'] }})"
                            class="px-2 pt-2 bg-[var(--md-sys-color-surface-container-highest)] rounded-xl text-[var(--md-sys-color-secondary)] shadow-sm hover:bg-[var(--md-sys-color-secondary)] hover:text-[var(--md-sys-color-on-secondary)] transition-colors"
                            title="اشتراک‌گذاری"
                        >
                            <span class="material-symbols-rounded text-[16px]">share</span>
                        </button>
                        <button
                            wire:click="editEvent({{ $event['id'] }})"
                            class="px-2 pt-2 bg-[var(--md-sys-color-surface-container-highest)] rounded-xl text-[var(--md-sys-color-primary)] shadow-sm hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-[16px]">edit</span>
                        </button>
                        <button
                            wire:click="confirmDelete({{ $event['id'] }})"
                            class="px-2 pt-2 bg-[var(--md-sys-color-error-container)] rounded-xl text-[var(--md-sys-color-on-error-container)] shadow-sm hover:bg-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-[16px]">delete</span>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <x-ui.empty icon="calendar_today" title="رویدادی یافت نشد" description="برای این روز هنوز هیچ برنامه ای ثبت نکرده‌اید." variant="list" :fill="true" />
        @endforelse
    </div>
</div>
