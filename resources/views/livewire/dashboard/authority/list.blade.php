<div class="space-y-2 !animate-slide-over-in">
    @forelse($this->authorities as $index => $authority)
        @php
            $details  = $authority->details ?? [];
            $approved = $details['approved_delegation'] ?? null;
            $badge    = $presenter->delegationBadge($approved);
        @endphp

        <div
            wire:key="authority-{{ $authority->id }}"
            x-data="{ open: false }"
            @authority-toggle-all.window="open = $event.detail.open"
            class="rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] overflow-hidden transition-all duration-200"
            :class="open ? 'border-[var(--md-sys-color-outline)]' : ''"
        >
            <button
                @click="open = !open"
                class="w-full flex items-center gap-3 px-5 py-3.5 text-right transition-colors hover:bg-[var(--md-sys-color-surface-variant)]/30 focus:outline-none"
            >
                <span
                    class="shrink-0 text-[11px] font-mono font-bold text-[var(--md-sys-color-on-surface-variant)] w-6 text-center">
                    {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                </span>

                <div class="flex-1 min-w-0 text-start">
                    <p class="text-sm font-semibold text-[var(--md-sys-color-on-surface)] leading-snug truncate">
                        {{ $details['duty'] ?? '—' }}
                    </p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5 flex items-center gap-1">
                        <span
                            class="material-symbols-rounded text-[13px]">{{ $presenter->responsibleIcon($authority) }}</span>
                        {{ $presenter->responsibleLabel($authority) }}
                    </p>
                </div>

                @if($approved)
                    <span class="hidden sm:inline text-[10px] font-medium px-2 py-0.5 rounded {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                @endif

                <span
                    class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200"
                    :class="open ? 'rotate-180' : ''">
                    expand_more
                </span>
            </button>

            <div x-show="open" x-collapse class="overflow-hidden">
                <div class="border-t border-[var(--md-sys-color-outline-variant)]/60">

                    @php( $coDelegate = $details['co_delegate'] ?? null)
                    @if($approved || $coDelegate)
                        <div
                            class="px-5 py-2.5 flex flex-wrap items-center gap-x-8 gap-y-1.5 border-b border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)]/50">
                            @if($approved)
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-[10px] uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)]">تفویض مصوب</span>
                                    <span
                                        class="text-[10px] font-semibold px-2 py-0.5 rounded {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </div>
                            @endif
                            @if($coDelegate)
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-[10px] uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)]">تفویض مشترک</span>
                                    <span
                                        class="text-xs font-medium text-[var(--md-sys-color-on-surface)]">{{ $coDelegate }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($activeTab === 'manager')
                        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]/40">
                            @foreach($presenter->managerRows($details) as $row)
                                <div class="flex items-center gap-3 px-5 py-2.5">
                                    <span
                                        class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-on-surface-variant)] shrink-0">{{ $row['icon'] }}</span>
                                    <span
                                        class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] w-28 shrink-0">{{ $row['label'] }}</span>
                                    <span
                                        class="text-xs font-medium {{ $row['color'] ?? 'text-[var(--md-sys-color-on-surface)]' }}">{{ $row['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        </div>

    @empty
        <div
            class="flex flex-col items-center justify-center gap-2 py-16 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]">
            <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-on-surface-variant)] opacity-40">policy</span>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] opacity-60">هیچ اختیاری یافت نشد</p>
        </div>
    @endforelse

    @if($this->authorities->hasMorePages())
        <div class="flex justify-center py-6 pb-12">
            <x-ui.buttons.load-more
                action="loadMore"
                text="بارگذاری بیشتر"
                loading-text="در حال دریافت..."
                icon="expand_more"
                wire:island="authorities"
                class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
            />
        </div>
    @endif
</div>
