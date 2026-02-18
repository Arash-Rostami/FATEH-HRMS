<div class="h-full flex flex-col p-5 overflow-hidden relative">

    {{-- Decorative Background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-[var(--md-sys-color-surface-container)]/50 via-transparent to-[var(--md-sys-color-surface)]/80 pointer-events-none"></div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 relative z-10 shrink-0">
        <div class="flex flex-col">
            <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] font-yekan leading-tight">
                {{ \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $selectedDate)->format('l') }}
            </h3>
            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                {{ \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $selectedDate)->format('d F Y') }}
            </span>
        </div>
        <div class="bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] px-3 py-1.5 rounded-full text-xs font-bold shadow-sm ring-1 ring-[var(--md-sys-color-secondary)]/10">
            {{ count($this->selectedDayEvents) }} برنامه
        </div>
    </div>

    {{-- Scroll Area --}}
    <div class="flex-1 overflow-y-auto container-scrollbar custom-scrollbar pr-1 relative z-10 -mr-2 pb-4 space-y-3">
        @forelse($this->selectedDayEvents as $event)
            <div
                wire:key="event-{{ $event['id'] }}"
                class="group relative bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-[24px] p-4 transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/40 hover:shadow-lg hover:-translate-y-0.5"
            >
                {{-- Side Strip Color --}}
                <div class="absolute right-0 top-4 bottom-4 w-1 rounded-l-full" style="background-color: {{ $event['color'] ?? 'var(--md-sys-color-primary)' }}"></div>

                <div class="flex gap-4 items-center">
                    {{-- Icon Box --}}
                    <div class="shrink-0 relative">
                        @if(isset($event['image']) && $event['image'])
                            <div class="w-14 h-14 rounded-2xl overflow-hidden shadow-md ring-2 ring-white dark:ring-[var(--md-sys-color-outline)]">
                                <img src="{{ $event['image'] }}" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-md transition-transform group-hover:scale-105"
                                 style="background-color: {{ $event['color'] ?? 'var(--md-sys-color-primary)' }}15; color: {{ $event['color'] ?? 'var(--md-sys-color-primary)' }}">
                                <span class="material-symbols-rounded text-[28px]">{{ $event['icon'] ?? 'event' }}</span>
                            </div>
                        @endif

                        {{-- Owner Indicator --}}
                        @if($event['is_owner'])
                            <div class="absolute -bottom-1 -left-1 bg-[var(--md-sys-color-primary-container)] rounded-full p-0.5 border-2 border-[var(--md-sys-color-surface)] shadow-sm" title="شما">
                                <span class="material-symbols-rounded text-[10px] text-[var(--md-sys-color-on-primary-container)]">person</span>
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0 pr-2">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="font-bold text-[var(--md-sys-color-on-surface)] truncate text-base group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                                {{ $event['title'] }}
                            </h4>
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] group-hover:bg-[var(--md-sys-color-primary-container)] group-hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                                {{ $event['time'] }}
                            </span>
                        </div>

                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] opacity-80 line-clamp-2 leading-relaxed mb-2">
                            {{ $event['description'] ?: 'بدون توضیحات' }}
                        </p>

                        {{-- Metadata Badges --}}
                        <div class="flex items-center gap-2">
                            @if($event['private'])
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary-container)]/50 px-2 py-0.5 rounded-md">
                                    <span class="material-symbols-rounded text-[12px]">lock</span>
                                    خصوصی
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[var(--md-sys-color-tertiary)] bg-[var(--md-sys-color-tertiary-container)]/50 px-2 py-0.5 rounded-md">
                                    <span class="material-symbols-rounded text-[12px]">public</span>
                                    عمومی
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Overlay (Owner Only) --}}
                @if($event['is_owner'])
                    <div class="absolute top-2 left-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-x-2 group-hover:translate-x-0">
                        <button
                            wire:click="editEvent({{ $event['id'] }})"
                            class="p-2 bg-[var(--md-sys-color-surface)]/90 backdrop-blur rounded-full text-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md hover:scale-110 active:scale-95 transition-all border border-[var(--md-sys-color-outline-variant)]/20"
                            title="ویرایش"
                        >
                            <span class="material-symbols-rounded text-[18px]">edit</span>
                        </button>
                        <button
                            wire:click="confirmDelete({{ $event['id'] }})"
                            class="p-2 bg-[var(--md-sys-color-error-container)]/90 backdrop-blur rounded-full text-[var(--md-sys-color-on-error-container)] shadow-sm hover:shadow-md hover:scale-110 active:scale-95 transition-all border border-[var(--md-sys-color-error)]/20"
                            title="حذف"
                        >
                            <span class="material-symbols-rounded text-[18px]">delete</span>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center text-center py-12 px-4 opacity-60">
                <div class="w-24 h-24 bg-[var(--md-sys-color-surface-container-high)] rounded-[32px] flex items-center justify-center mb-4 shadow-inner ring-4 ring-[var(--md-sys-color-surface)]">
                    <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-outline)] animate-pulse">event_note</span>
                </div>
                <h4 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-1">روز خالی!</h4>
                <p class="text-sm text-[var(--md-sys-color-outline)] mb-4 max-w-[200px] leading-relaxed">
                    هیچ رویدادی برای این روز ثبت نشده است. اولین نفر باشید!
                </p>
                <button
                    wire:click="openCreateModal"
                    class="text-[var(--md-sys-color-primary)] font-bold text-sm hover:bg-[var(--md-sys-color-primary-container)] px-4 py-2 rounded-xl transition-colors flex items-center gap-2"
                >
                    <span class="material-symbols-rounded">add_circle</span>
                    افزودن رویداد جدید
                </button>
            </div>
        @endforelse
    </div>
</div>
