                @php
                    $tdBase = 'border-b border-[var(--md-sys-color-outline-variant)] px-4 py-2.5 align-middle';
                    $tdCenter = "whitespace-nowrap {$tdBase} text-center";
                    $iconBtn = 'p-1 rounded-lg transition-colors';
                @endphp

                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1 overflow-x-auto">
                        @foreach($filterOptions as $key => $label)
                            <button wire:click="setFilter('{{ $key }}')"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap transition-colors {{ $filter === $key ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <button type="button" wire:click="openCreate" title="یادآوری جدید"
                            class="shrink-0 p-1.5 rounded-lg text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] transition-colors">
                        <span class="material-symbols-rounded text-[18px]">add</span>
                    </button>
                </div>

                @if($this->reminders->isEmpty())
                    <div wire:key="reminder-empty-list" class="flex min-h-[300px] items-center justify-center rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">
                        <x-ui.empty icon="alarm_off"
                                    title="یادآوری‌ای یافت نشد"
                                    description="برای ساختن یادآوری از دکمه + یا تب «یادآوری جدید» استفاده کنید"
                                    fill />
                    </div>
                @else
                    <div wire:key="reminder-rows" class="relative overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">
                        <div class="w-full overflow-x-auto min-h-[300px] max-h-[50vh] overflow-y-auto scrollbar-hover-reveal">
                            <table class="min-w-full w-full border-separate border-spacing-0 text-sm">
                                <thead class="sticky top-0 z-10 bg-[var(--md-sys-color-surface-container-high)] text-xs uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]">
                                <tr>
                                    @foreach($columns as $col)
                                        <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 py-2.5 {{ $col['align'] }} font-bold">{{ $col['label'] }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($this->reminders as $reminder)
                                    @php($presenter = $this->presenter($reminder))
                                    <tr wire:key="reminder-row-{{ $reminder->id }}" class="hover:bg-[var(--md-sys-color-surface-container-low)] transition-colors">
                                        <td class="{{ $tdBase }} text-right">
                                            <p class="text-sm font-bold {{ $reminder->completed_at ? 'line-through opacity-60' : '' }}">{{ $reminder->title }}</p>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                                <span class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">
                                                    <span class="material-symbols-rounded text-[12px]">{{ $presenter->hostIcon() }}</span>
                                                    {{ $presenter->hostLabel() }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="{{ $tdCenter }}">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold border {{ $presenter->toneClasses() }}">
                                                <span class="material-symbols-rounded text-[13px]">{{ $presenter->statusIcon() }}</span>
                                                {{ $presenter->dueLabel() }}
                                            </span>
                                            @if($presenter->statusTimingLabel())
                                                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70 mt-1">{{ $presenter->statusTimingLabel() }}</p>
                                            @endif
                                        </td>
                                        <td class="{{ $tdCenter }}">
                                            @if($reminder->recurs->value !== 'none')
                                                <span wire:key="reminder-recur-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                                    <span class="material-symbols-rounded text-[12px]">autorenew</span>
                                                    {{ $presenter->recurLabel() }}
                                                </span>
                                            @else
                                                <span wire:key="reminder-recur-none" class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-outline)]" title="بدون تکرار">close</span>
                                            @endif
                                        </td>
                                        <td class="{{ $tdCenter }}">
                                            <div class="flex items-center justify-center gap-1">
                                                @foreach($presenter->channelBadges() as $badge)
                                                    <span title="{{ $badge['label'] }}" class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-on-surface-variant)]">{{ $badge['icon'] }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="{{ $tdCenter }}">
                                            <div class="flex items-center justify-center gap-0.5">
                                                @unless($reminder->completed_at)
                                                    <button wire:click="complete({{ $reminder->id }})" title="انجام شد" class="{{ $iconBtn }} text-[var(--md-sys-color-success)] hover:bg-[var(--md-sys-color-success-container)]">
                                                        <span class="material-symbols-rounded text-[16px]">check_circle</span>
                                                    </button>
                                                    <div x-data="{ open: false, top: 0, left: 0, toggle(e) { if (this.open) { this.open = false; return } const r = e.currentTarget.getBoundingClientRect(); this.top = r.bottom + 4; this.left = r.left; this.open = true } }" class="relative">
                                                        <button @click="toggle($event)" title="خاموش‌کردن موقت اعلان (سررسید تغییر نمی‌کند)" class="{{ $iconBtn }} text-[var(--md-sys-color-secondary)] hover:bg-[var(--md-sys-color-secondary-container)]">
                                                            <span class="material-symbols-rounded text-[16px]">snooze</span>
                                                        </button>
                                                        <template x-teleport="body">
                                                            <div x-show="open" x-cloak @click.outside="open = false" x-data="{ hours: '' }"
                                                                 :style="`position:fixed; top:${top}px; left:${left}px;`"
                                                                 class="w-40 bg-[var(--md-sys-color-surface)] rounded-xl shadow-lg border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden z-[200] text-[var(--md-sys-color-on-surface)]">
                                                                @foreach($snoozePresets as $preset => $label)
                                                                    <button x-on:click="open = false; $wire.snooze({{ $reminder->id }}, '{{ $preset }}')" class="w-full text-right px-3 py-1.5 text-[11px] hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors">
                                                                        {{ $label }}
                                                                    </button>
                                                                @endforeach
                                                                <div class="flex items-center gap-1 px-2 py-1.5 border-t border-[var(--md-sys-color-outline-variant)]/20">
                                                                    <select x-model="hours" class="w-16 text-center text-[11px] rounded-lg border border-[var(--md-sys-color-outline-variant)] bg-transparent px-1 py-1">
                                                                        <option value="">ساعت</option>
                                                                        @for($h = 1; $h <= 12; $h++)
                                                                            <option value="{{ $h }}">{{ $h }}</option>
                                                                        @endfor
                                                                    </select>
                                                                    <button @click="if(hours){ open = false; $wire.snoozeHours({{ $reminder->id }}, hours) }"
                                                                            class="flex-1 text-[11px] font-bold rounded-lg px-2 py-1 text-[var(--md-sys-color-secondary)] hover:bg-[var(--md-sys-color-secondary-container)] transition-colors">
                                                                        اعمال
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <button wire:click="edit({{ $reminder->id }})" title="ویرایش" class="{{ $iconBtn }} text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)]">
                                                        <span class="material-symbols-rounded text-[16px]">edit</span>
                                                    </button>
                                                    @if($reminder->recurs->value !== 'none')
                                                        <button wire:key="reminder-stop-recurrence" wire:click="stopRecurrence({{ $reminder->id }})" title="توقف تکرار" class="{{ $iconBtn }} text-[var(--md-sys-color-warning)] hover:bg-[var(--md-sys-color-warning-container)]">
                                                            <span class="material-symbols-rounded text-[16px]">block</span>
                                                        </button>
                                                    @endif
                                                @endunless
                                                <button wire:click="delete({{ $reminder->id }})" wire:confirm="یادآوری حذف شود؟" title="حذف" class="{{ $iconBtn }} text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)]">
                                                    <span class="material-symbols-rounded text-[16px]">delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($this->reminders->hasMorePages())
                        <div wire:key="reminder-load-more" class="flex justify-center pt-4">
                            <x-ui.buttons.load-more
                                action="loadMore"
                                text="بارگذاری بیشتر"
                                loading-text="در حال دریافت..."
                                icon="expand_more"
                                class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
                            />
                        </div>
                    @endif
                @endif
