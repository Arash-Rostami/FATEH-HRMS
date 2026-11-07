@php($letter = $presenter->letter($report->spec))

@if($letter !== null)
    <div wire:key="ai-report-letter" dir="{{ $letter['dir'] }}" x-data="{ open: false }" class="print:hidden">
        <x-ui.notice-card icon="mail">
            <div class="flex items-start justify-between gap-2">
                <h2 @click="open = !open" class="min-w-0 cursor-pointer text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $letter['title'] }}</h2>
                <div class="flex items-center gap-1 shrink-0">
                    <x-ui.buttons.form wire:key="ai-report-letter-copy" type="button" variant="tonal" size="icon" icon="content_copy"
                                       title="رونوشت خلاصه برای ارسال" aria-label="رونوشت خلاصه برای ارسال" class="print:hidden shrink-0 !h-8 !w-8 !p-0 !rounded-lg"
                                       x-data="aiReportCopy" data-toast="خلاصهٔ گزارش رونوشت شد." data-text="{{ $letter['text'] }}"
                                       x-on:click="copyLetter()"/>
                    <button type="button" @click="open = !open" :aria-expanded="open" title="نمایش یا پنهان‌کردن خلاصه" aria-label="نمایش یا پنهان‌کردن خلاصه"
                            class="h-8 w-8 shrink-0 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded !text-[20px] leading-none transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                    </button>
                </div>
            </div>
            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            @if($letter['figure'] !== null)
                <div wire:key="ai-report-letter-figure" class="mt-3 flex flex-col gap-1 p-3.5 rounded-xl bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)]/40 overflow-hidden">
                    <p class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] truncate" dir="auto">{{ $letter['figure']['label'] }}</p>
                    <p class="text-base font-black tracking-tight break-words tabular-nums text-[var(--md-sys-color-on-surface)]"><bdi>{{ $letter['figure']['value'] }}</bdi></p>
                </div>
            @endif
            @if($letter['decision'] !== null)
                <div wire:key="ai-report-letter-decision" class="mt-3 flex flex-col gap-1">
                    <p class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $letter['labels']['decision'] }}</p>
                    <p class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $letter['decision']['heading'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $letter['decision']['text'] }}</p>
                </div>
            @endif
            @if($letter['assumption'] !== null)
                <div wire:key="ai-report-letter-assumption" class="mt-3 flex flex-col gap-1">
                    <p class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $letter['labels']['assumption'] }}</p>
                    <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $letter['assumption'] }}</p>
                </div>
            @endif
            </div>
        </x-ui.notice-card>
    </div>
@endif
