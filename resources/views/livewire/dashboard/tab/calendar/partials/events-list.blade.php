<div class="h-full flex flex-col p-4 overflow-y-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">
            {{ \Morilog\Jalali\Jalalian::fromFormat('Y-m-d', $selectedDate)->format('l, d F') }}
        </h3>
        <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container)] px-2 py-1 rounded-full">
            {{ count($this->selectedDayEvents) }} رویداد
        </span>
    </div>

    {{-- Events Loop --}}
    <div class="space-y-3 flex-1">
        @forelse($this->selectedDayEvents as $event)
            <div
                wire:key="event-{{ $event['id'] }}"
                class="group relative bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-2xl p-4 transition-all border border-[var(--md-sys-color-outline-variant)] hover:shadow-md"
            >
                <div class="flex gap-4">
                    {{-- Icon / Avatar --}}
                    <div class="shrink-0">
                        @if(isset($event['avatar']) && $event['avatar'])
                            <img src="{{ $event['avatar'] }}" class="w-12 h-12 rounded-full object-cover border-2 border-[var(--md-sys-color-primary)]">
                        @else
                            <div class="w-12 h-12 rounded-full bg-[var(--md-sys-color-tertiary-container)] flex items-center justify-center text-[var(--md-sys-color-on-tertiary-container)]">
                                @if($event['type'] === 'birthday')
                                    <span class="material-symbols-rounded">cake</span>
                                @elseif($event['type'] === 'anniversary')
                                    <span class="material-symbols-rounded">celebration</span>
                                @else
                                    <span class="material-symbols-rounded">event</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <h4 class="font-bold text-[var(--md-sys-color-on-surface)] truncate">
                                {{ $event['title'] }}
                            </h4>
                            <span class="text-xs font-mono text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)] px-1.5 py-0.5 rounded">
                                {{ $event['time'] }}
                            </span>
                        </div>

                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mt-1 line-clamp-2">
                            {{ $event['description'] }}
                        </p>

                        {{-- Metadata --}}
                        <div class="flex items-center gap-2 mt-3 text-xs text-[var(--md-sys-color-outline)]">
                            @if($event['private'])
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[14px]">lock</span>
                                    خصوصی
                                </span>
                            @else
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[14px]">public</span>
                                    عمومی
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Actions (Owner Only) --}}
                @if($event['is_owner'])
                    <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                        <button
                            wire:click="editEvent({{ $event['id'] }})"
                            class="p-1.5 bg-[var(--md-sys-color-surface)] rounded-full text-[var(--md-sys-color-primary)] shadow-sm hover:scale-110 transition-transform"
                            title="ویرایش"
                        >
                            <span class="material-symbols-rounded text-[16px]">edit</span>
                        </button>
                        <button
                            wire:click="confirmDelete({{ $event['id'] }})"
                            class="p-1.5 bg-[var(--md-sys-color-surface)] rounded-full text-[var(--md-sys-color-error)] shadow-sm hover:scale-110 transition-transform"
                            title="حذف"
                        >
                            <span class="material-symbols-rounded text-[16px]">delete</span>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            {{-- Empty State --}}
            <div class="h-full flex flex-col items-center justify-center text-center opacity-60">
                <div class="w-20 h-20 bg-[var(--md-sys-color-surface-variant)] rounded-full flex items-center justify-center mb-4">
                    <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-surface-variant)]">event_busy</span>
                </div>
                <p class="text-[var(--md-sys-color-on-surface)] font-medium">هیچ رویدادی نیست</p>
                <p class="text-sm text-[var(--md-sys-color-outline)] mt-1">برای افزودن، دکمه + را بزنید</p>
            </div>
        @endforelse
    </div>
</div>
