<x-ui.modals.base
    wire:model="dailyOpen"
    contentClass="!w-full md:!w-[64rem] !max-w-[96vw] bg-[var(--md-sys-color-surface)] rounded-2xl shadow-2xl overflow-x-hidden h-[88vh] !p-0 border border-[var(--md-sys-color-outline-variant)]/30 text-right"
>
    <div dir="rtl"
         x-data="{ ready: false, viewer: false, src: '', caption: '' }"
         x-on:keydown.escape.window.capture="if (viewer) { viewer = false; $event.stopPropagation() }"
         x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
         x-show="ready">

        @if($dailyOpen)
            @php($data = $this->dailyData)
            <div wire:key="reservation-daily-controls" class="flex flex-col gap-3 border-b border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] p-4 sm:p-5">
                <div class="[&>div]:!mb-0">
                    <x-ui.title icon="groups" title="رزروهای همکاران" />
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <x-ui.buttons.tab-selector :tabs="$dailyQuickTabs" class="!mb-0" />
                </div>

                <div x-show="$wire.daily.preset === 'custom'" x-cloak>
                    @include('livewire.dashboard.reservation.daily-range', ['monthOptions' => $dailyMonthOptions, 'yearOptions' => $dailyYearOptions])
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if(($daily['scope'] ?? 'day') !== 'custom')
                        <div wire:key="reservation-daily-nav" class="flex items-center gap-1">
                            <x-ui.buttons.form variant="tonal" size="icon" icon="chevron_right" wire:click="shiftDaily(-1)" title="قبلی" aria-label="بازهٔ قبلی" />
                            <x-ui.buttons.form variant="tonal" size="icon" icon="chevron_left" wire:click="shiftDaily(1)" title="بعدی" aria-label="بازهٔ بعدی" />
                        </div>
                    @endif
                    <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $data['label'] }}</span>
                    <x-ui.badge variant="info">{{ convertToPersian((string) $data['total']) }} رزرو · {{ convertToPersian((string) $data['resources']) }} منبع</x-ui.badge>
                </div>

                <div class="flex flex-col gap-3">
                    <x-ui.forms.search name="dailySearch" model="daily.search" placeholder="جستجوی منبع، مشخصات (طبقه، داخلی…) یا نام همکار" />
                    <x-ui.forms.select label="نوع منبع" name="dailyType" wire:model.live="daily.type" icon="category">
                        <option value="all">همه منابع</option>
                        @foreach($this->bookableTabs as $type)
                            <option value="{{ $type['id'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </x-ui.forms.select>
                    <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                        <x-ui.forms.switch name="daily.mine" :live="true" />
                        <span>فقط رزروهای من</span>
                    </label>
                </div>
            </div>

            @include('livewire.dashboard.reservation.daily-rows')

            @if($data['shown'] > 0)
                <div wire:key="reservation-daily-footer" class="sticky bottom-0 z-10 grid grid-cols-[1fr_auto_1fr] items-center gap-3 border-t border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)] px-4 py-3">
                    <span></span>
                    <div class="flex justify-center">
                        @if($data['more'])
                            <x-ui.buttons.load-more wire:key="reservation-daily-load-more" action="loadMoreDaily" text="موارد بیشتر" loadingText="در حال بارگذاری…" icon="expand_more" class="rounded-xl bg-[var(--md-sys-color-primary-container)] px-4 py-2 text-xs font-bold text-[var(--md-sys-color-on-primary-container)]" />
                        @endif
                    </div>
                    <span class="justify-self-end text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                        نمایش {{ convertToPersian((string) $data['shown']) }} از {{ convertToPersian((string) $data['total']) }} رزرو
                        @if($data['capped'])
                            · برای دیدن بقیه، بازه یا فیلتر را محدودتر کنید
                        @endif
                    </span>
                </div>
            @endif
        @endif

        <x-ui.modals.lightbox state="viewer">
            <div class="flex flex-col items-center gap-3">
                <img :src="src" :alt="caption" class="max-h-[80vh] w-auto max-w-full rounded-2xl object-contain shadow-2xl">
                <x-ui.badge variant="info" x-text="caption"></x-ui.badge>
            </div>
        </x-ui.modals.lightbox>
    </div>
</x-ui.modals.base>
