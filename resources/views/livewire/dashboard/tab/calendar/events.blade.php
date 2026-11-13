<div
    wire:key="day-panel-{{ $this->activeDate['gregorian'] ?? 'default' }}"
    x-data="{ activeFilter: 'all' }"
    class="h-full flex flex-col p-4 md:p-6 overflow-hidden bg-transparent select-none"
>
    @php
        $d = $this->activeDate;
        $eventTypes = collect($this->selectedDayEvents)
            ->map(fn($e) => $e['type'] ?? 'default')
            ->unique()
            ->values();
    @endphp

    <div class="flex items-center justify-between mb-4 pb-4 shrink-0 gap-4 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]">
        <div class="flex items-center gap-4 min-w-0">
            <div class="relative flex items-center justify-center shrink-0">
                <span class="material-symbols-rounded text-[34px] text-[var(--md-sys-color-primary)] transition-transform duration-300 hover:scale-105" style="font-variation-settings: 'FILL' 1, 'wght' 500;">calendar_month</span>
            </div>
            <div class="flex flex-col justify-center min-w-0 gap-1">
                <div class="flex items-baseline gap-2">
                    <h3 class="text-xl md:text-2xl font-black text-[var(--md-sys-color-on-surface)] tracking-tight truncate leading-tight">
                        {{ convertToPersian($d['jalali']) }}
                    </h3>
                </div>
                <div class="flex items-center gap-1.5 opacity-85">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">public</span>
                    <span dir="ltr" class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] tabular-nums tracking-[0.14em] uppercase">
                        {{ $d['gregorian'] }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center min-w-[3.25rem] px-3.5 py-2 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_75%,transparent)] backdrop-blur-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] shadow-[0_2px_10px_-2px_color-mix(in_srgb,var(--md-sys-color-shadow)_8%,transparent),inset_0_1px_0_0_color-mix(in_srgb,var(--md-sys-color-on-surface)_8%,transparent)] rounded-2xl shrink-0">
            <span class="text-base font-black text-[var(--md-sys-color-primary)] leading-none tabular-nums tracking-tight">
                {{ convertToPersian(count($this->selectedDayEvents)) }}
            </span>
        </div>
    </div>

    {{-- Enhancement 1: Auto-appearing Client-Side Filter Pills (only shows when multiple event types exist) --}}
    @if($eventTypes->count() > 1)
        <div class="flex items-center gap-1.5 mb-3.5 shrink-0 overflow-x-auto scrollbar-hide pb-0.5">
            <button type="button" x-on:click="activeFilter = 'all'"
                    :class="activeFilter === 'all' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-xs' : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_65%,transparent)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]'"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all duration-200 shrink-0">
                همه ({{ convertToPersian(count($this->selectedDayEvents)) }})
            </button>
            @foreach($eventTypes as $type)
                @php
                    $typeCount = collect($this->selectedDayEvents)->filter(fn($e) => ($e['type'] ?? 'default') === $type)->count();
                @endphp
                <button type="button" x-on:click="activeFilter = '{{ $type }}'"
                        :class="activeFilter === '{{ $type }}' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-xs' : 'bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_65%,transparent)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]'"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all duration-200 shrink-0">
                    {{ match($type) { 'birthday' => 'تولد', 'anniversary' => 'سالگرد', 'holiday' => 'تعطیلات', 'reminder' => 'یادآور', default => 'رویداد' } }}
                    <span class="opacity-75 text-[10px]">({{ convertToPersian($typeCount) }})</span>
                </button>
            @endforeach
        </div>
    @endif

    <div class="flex-1 overflow-y-auto space-y-3.5 pr-1 -mr-2 scrollbar-hide hover:scrollbar-default pb-2 mt-1">
        @forelse ($this->selectedDayEvents as $event)
            @php
                $rangeLabel = $presenter->dayEventRangeLabel($event);
                $eventType = $event['type'] ?? 'default';
            @endphp
            <div
                wire:key="event-{{ $event['id'] }}"
                data-rf="calendar-{{ $event['id'] }}"
                x-show="activeFilter === 'all' || activeFilter === '{{ $eventType }}'"
                x-transition.opacity.duration.200ms
                class="group relative overflow-hidden bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_70%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-surface-container)_90%,transparent)] backdrop-blur-md rounded-xl p-4 transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] shadow-[0_2px_8px_-2px_color-mix(in_srgb,var(--md-sys-color-shadow)_6%,transparent),inset_0_1px_0_0_color-mix(in_srgb,var(--md-sys-color-on-surface)_5%,transparent)] hover:shadow-[0_10px_28px_-6px_color-mix(in_srgb,var(--md-sys-color-primary)_16%,transparent)] hover:-translate-y-[1px] slide-up"
                style="animation-delay: {{ $loop->index * 0.05 }}s"
            >
                <div class="absolute inset-0 bg-gradient-to-br from-[color-mix(in_srgb,var(--md-sys-color-primary)_7%,transparent)] via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

                <div class="relative z-10 flex gap-4">
                    <div class="shrink-0 relative self-start">
                        @if(!empty($event['avatar']))
                            <span class="flex w-12 h-12 rounded-[28%] ring-2 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] group-hover:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] shadow-sm transition-all duration-300 overflow-hidden">
                                <x-ui.avatar :existingImage="$event['avatar']" alt="" class="!rounded-[28%] w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"/>
                            </span>
                            @if(($event['type'] ?? '') === 'birthday')
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-[var(--tool-amethyst-container,var(--md-sys-color-tertiary-container))] text-[var(--tool-amethyst-on-container,var(--md-sys-color-on-tertiary-container))] rounded-xl flex items-center justify-center border border-[var(--md-sys-color-surface)] shadow-md">
                                    <span class="material-symbols-rounded text-[12px]" style="font-variation-settings: 'FILL' 1;">cake</span>
                                </div>
                            @elseif(($event['type'] ?? '') === 'anniversary')
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-[var(--tool-gold-container,var(--md-sys-color-secondary-container))] text-[var(--tool-gold-on-container,var(--md-sys-color-on-secondary-container))] rounded-xl flex items-center justify-center border border-[var(--md-sys-color-surface)] shadow-md">
                                    <span class="material-symbols-rounded text-[12px]" style="font-variation-settings: 'FILL' 1;">celebration</span>
                                </div>
                            @endif
                        @else
                            <div class="w-12 h-12 rounded-xl {{ getEventStyles($event['type'] ?? '') }} flex items-center justify-center shadow-[inset_0_1px_0_0_rgba(255,255,255,0.15)] ring-1 ring-inset ring-black/5 transition-transform duration-300 group-hover:scale-[1.03]">
                                @if(($event['type'] ?? '') === 'birthday')
                                    <span class="material-symbols-rounded text-[22px]" style="font-variation-settings: 'FILL' 1;">cake</span>
                                @elseif(($event['type'] ?? '') === 'anniversary')
                                    <span class="material-symbols-rounded text-[22px]" style="font-variation-settings: 'FILL' 1;">celebration</span>
                                @elseif(($event['type'] ?? '') === 'holiday')
                                    <span class="material-symbols-rounded text-[22px]" style="font-variation-settings: 'FILL' 1;">event_busy</span>
                                @elseif(($event['type'] ?? '') === 'reminder')
                                    <span class="material-symbols-rounded text-[22px]" style="font-variation-settings: 'FILL' 1;">alarm</span>
                                @else
                                    <span class="material-symbols-rounded text-[22px]">event</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0 pt-0.5">
                        <div class="flex justify-between items-start gap-2.5 mb-1.5">
                            {{-- Enhancement 5: select-text enabled on title --}}
                            <h4 class="select-text font-bold text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors duration-200 break-words min-w-0 flex-1 text-sm md:text-[15px] leading-snug tracking-tight">
                                {{ $event['title'] }}
                            </h4>

                            <div class="flex items-center gap-1.5 shrink-0">
                                {{-- Enhancement 4: Unique wire:key + Sent Confirmation Badge --}}
                                @if($event['can_sms'] ?? false)
                                    <div class="shrink-0 flex items-center" x-data="{ smsData: {{ json_encode(['scope' => 'calendar', 'id' => $event['user_id'], 'name' => $event['sms_name'], 'type' => $event['type'], 'key' => $event['sms_key']]) }} }">
                                        <x-ui.buttons.form
                                            wire:key="calendar-detail-sms-{{ $event['id'] }}"
                                            variant="none" size="icon" icon="sms"
                                            title="ارسال پیامک تبریک" aria-label="ارسال پیامک تبریک"
                                            class="shrink-0 !w-7 !h-7 !rounded-lg border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_50%,transparent)] text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] hover:border-transparent shadow-xs transition-all duration-200 [&>span]:!text-[15px]"
                                            x-cloak x-show="!smsAlreadySent(smsData.key)"
                                            x-on:click.stop="$dispatch('open-sms', smsData)"
                                        />
                                        <span
                                            x-cloak x-show="smsAlreadySent(smsData.key)"
                                            title="پیامک تبریک ارسال شده است"
                                            class="inline-flex items-center gap-0.5 text-[10px] font-bold text-[var(--md-sys-color-primary)] bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_45%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] px-2 py-1 rounded-lg"
                                        >
                                            <span class="material-symbols-rounded text-[12px]">check</span>
                                            <span>ارسال شد</span>
                                        </span>
                                    </div>
                                @endif

                                @if($rangeLabel !== '')
                                    <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)] bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_65%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] px-2.5 py-1 rounded-lg shrink-0 tracking-wide">
                                        {{ $rangeLabel }}
                                    </span>
                                @endif

                                @if(($event['type'] ?? '') === 'reminder' && ($event['is_snoozed'] ?? false))
                                    <span class="flex items-center gap-1 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container-highest)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] px-2 py-1 rounded-lg shrink-0" title="اعلان موقتاً خاموش است">
                                        <span class="material-symbols-rounded text-[12px]">snooze</span>
                                        {{ convertToPersian($event['snoozed_hours']) }} ساعت اعلان خاموش
                                    </span>
                                @endif
                            </div>
                        </div>

                        @php $eventDescription = $event['description'] ?? ''; @endphp
                        @if($eventDescription !== '')
                            {{-- Enhancement 5: select-text enabled on description --}}
                            <div x-data="{ expanded: false }" class="select-text text-xs md:text-[13px] text-[var(--md-sys-color-on-surface-variant)] leading-relaxed opacity-90 mt-1">
                                <div class="relative overflow-hidden transition-[max-height] duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
                                     :style="expanded ? ('max-height:' + $el.scrollHeight + 'px') : 'max-height: 3rem'">
                                    {{ $eventDescription }}
                                    @if(mb_strlen($eventDescription) > 55)
                                        <div x-show="!expanded" x-transition.opacity.duration.200ms class="absolute bottom-0 inset-x-0 h-6 pointer-events-none bg-gradient-to-t from-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_95%,transparent)] group-hover:from-[color-mix(in_srgb,var(--md-sys-color-surface-container)_95%,transparent)] to-transparent"></div>
                                    @endif
                                </div>
                                @if(mb_strlen($eventDescription) > 55)
                                    <button type="button" x-on:click.stop="expanded = !expanded" class="text-[var(--md-sys-color-primary)] text-[11px] font-semibold mt-1.5 inline-flex items-center gap-1 select-none rounded-lg px-2 py-0.5 -mx-2 transition-colors duration-200 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_50%,transparent)] hover:text-[var(--md-sys-color-on-primary-container)]">
                                        <span class="material-symbols-rounded text-[14px] transition-transform duration-300" :class="expanded ? 'rotate-180' : ''">expand_more</span>
                                        <span x-text="expanded ? 'بستن' : 'مشاهده بیشتر'"></span>
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if(!empty($event['is_reservation_linked']))
                            <div class="inline-flex items-center gap-1.5 flex-wrap text-[10px] font-bold text-[var(--md-sys-color-tertiary)] bg-[color-mix(in_srgb,var(--md-sys-color-tertiary-container)_55%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-tertiary)_20%,transparent)] px-2.5 py-1 rounded-md mt-3">
                                <span class="material-symbols-rounded text-[13px] shrink-0">event_seat</span>
                                <span>از طریق رزرو</span>
                                @if(!empty($event['long_hold_end_date']))
                                    <span class="tabular-nums opacity-90 border-r border-[color-mix(in_srgb,var(--md-sys-color-tertiary)_30%,transparent)] pr-1.5 mr-0.5">تا {{ $event['long_hold_end_date'] }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- Enhancement 2: Subtle opacity fade on desktop hover/focus when owner actions overlap --}}
                        <div class="flex items-center flex-wrap gap-2 mt-3 pt-2.5 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_22%,transparent)] transition-opacity duration-200 {{ $event['is_owner'] ? 'max-md:pb-9 md:group-hover:opacity-20 md:group-focus-within:opacity-20' : '' }}">
                            <div class="flex items-center gap-1 text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-high)_75%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] px-2 py-0.5 rounded-md">
                                @if($event['private'])
                                    <span class="material-symbols-rounded text-[12px] opacity-80">lock</span>
                                    <span>خصوصی</span>
                                @else
                                    <span class="material-symbols-rounded text-[12px] opacity-80">public</span>
                                    <span>عمومی</span>
                                @endif
                            </div>

                            @if(!empty($event['remind_hours']))
                                <div class="flex items-center gap-1 text-[10px] font-medium text-[var(--md-sys-color-error)] bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_45%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-error)_20%,transparent)] px-2 py-0.5 rounded-md"
                                     title="یادآوری {{ convertToPersian($event['remind_hours']) }} ساعت قبل">
                                    <span class="material-symbols-rounded text-[12px]">alarm</span>
                                    <span>{{ convertToPersian($event['remind_hours']) }} ساعت قبل</span>
                                </div>
                            @endif

                            @if(!empty($event['is_shared']))
                                <div class="flex items-center gap-1 text-[10px] font-medium text-[var(--md-sys-color-secondary)] bg-[color-mix(in_srgb,var(--md-sys-color-secondary-container)_50%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-secondary)_20%,transparent)] px-2 py-0.5 rounded-md">
                                    <span class="material-symbols-rounded text-[12px]">group</span>
                                    <span>
                                        مشترک
                                        @if(!empty($event['shared_by_name']))
                                            توسط {{ $event['shared_by_name'] }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Enhancement 2: Mobile-accessible & Keyboard-focusable Floating Action Buttons --}}
                @if($event['is_owner'] && !empty($event['is_reservation_linked']))
                    <div class="absolute bottom-2.5 left-3 z-20 flex items-center gap-1.5 opacity-100 md:opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] md:translate-x-2 group-hover:translate-x-0 group-focus-within:translate-x-0">
                        <x-ui.buttons.copy
                            :text="route('dashboard', ['tab' => 'calendar', 'open' => $event['id']])"
                            message="لینک رویداد کپی شد"
                            class="bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_90%,transparent)] backdrop-blur-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] !rounded-xl shadow-md hover:scale-105 transition-transform"
                        />
                        <a
                            href="{{ route('reservation') }}"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_90%,transparent)] backdrop-blur-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] rounded-xl text-[var(--md-sys-color-tertiary)] shadow-md hover:bg-[var(--md-sys-color-tertiary)] hover:text-[var(--md-sys-color-on-tertiary)] hover:border-transparent transition-all duration-200 text-[11px] font-bold"
                        >
                            <span class="material-symbols-rounded text-[15px]">open_in_new</span>
                            <span>مشاهده رزرو</span>
                        </a>
                    </div>
                @elseif($event['is_owner'])
                    <div class="absolute bottom-2.5 left-3 z-20 flex items-center gap-1.5 opacity-100 md:opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] md:translate-x-2 group-hover:translate-x-0 group-focus-within:translate-x-0">
                        <x-ui.buttons.copy
                            :text="route('dashboard', ['tab' => 'calendar', 'open' => $event['id']])"
                            message="لینک رویداد کپی شد"
                            class="bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_90%,transparent)] backdrop-blur-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] !rounded-xl shadow-md hover:scale-105 transition-transform"
                        />
                        <button
                            wire:click="openShareModal({{ $event['id'] }})"
                            class="w-8 h-8 inline-flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_90%,transparent)] backdrop-blur-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] rounded-xl text-[var(--md-sys-color-secondary)] shadow-md hover:bg-[var(--md-sys-color-secondary)] hover:text-[var(--md-sys-color-on-secondary)] hover:border-transparent hover:scale-105 transition-all duration-200"
                            title="اشتراک‌گذاری"
                        >
                            <span class="material-symbols-rounded text-[16px]">share</span>
                        </button>
                        <button
                            wire:click="editEvent({{ $event['id'] }})"
                            class="w-8 h-8 inline-flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_90%,transparent)] backdrop-blur-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)] rounded-xl text-[var(--md-sys-color-primary)] shadow-md hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] hover:border-transparent hover:scale-105 transition-all duration-200"
                            title="ویرایش"
                        >
                            <span class="material-symbols-rounded text-[16px]">edit</span>
                        </button>
                        <button
                            wire:click="confirmDelete({{ $event['id'] }})"
                            class="w-8 h-8 inline-flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_85%,transparent)] backdrop-blur-md border border-[color-mix(in_srgb,var(--md-sys-color-error)_25%,transparent)] rounded-xl text-[var(--md-sys-color-on-error-container)] shadow-md hover:bg-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error)] hover:border-transparent hover:scale-105 transition-all duration-200"
                            title="حذف"
                        >
                            <span class="material-symbols-rounded text-[16px]">delete</span>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div wire:key="calendar-day-empty" class="contents">
                <x-ui.empty icon="calendar_today" title="رویدادی یافت نشد" description="برای این روز هنوز هیچ برنامه ای ثبت نکرده‌اید." variant="list" :fill="true" />
            </div>
        @endforelse
    </div>
</div>
