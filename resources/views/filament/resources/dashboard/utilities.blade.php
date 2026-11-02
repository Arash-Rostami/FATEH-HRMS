@php
    $btn = 'group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition-all duration-200 snap-start snap-always hover:bg-[var(--md-sys-color-on-primary)]/12 text-[var(--md-sys-color-primary-container)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/40 focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--md-sys-color-surface)]';
    $persianDates = auth()->user()?->getPreference('persian_dates', true);
@endphp

<div
    class="relative ms-4 me-2 w-full max-w-[172px] md:w-max md:max-w-none md:min-w-0 border-x-0 md:border-e !border-[var(--header-border-color)] md:pl-0 md:pr-3">
    <div
        class="absolute top-1 -right-2 -translate-y-1/2 scale-75 z-20 flex md:hidden items-center justify-center w-5 h-5 rounded-md border border-[var(--md-sys-color-primary-container)]/40 bg-[var(--md-sys-color-primary)]/50 text-[var(--md-sys-color-primary-container)] backdrop-blur-sm pointer-events-none">
        <span class="material-symbols-rounded text-[14px] animate-pulse">swipe</span>
    </div>

    <div x-ref="scrollContainer"
         class="overflow-x-auto overflow-y-hidden overscroll-x-contain touch-pan-x snap-x snap-mandatory [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden pb-12 -mb-12">
        <div class="flex items-center gap-0.5 w-max px-1 md:px-3">
            <button type="button" @click="$store.filamentMenu.toggleFullscreen()" class="{{ $btn }}">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity"
                      x-text="$store.filamentMenu.fullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
                <x-ui.modals.tooltip text="تمام صفحه" position="bottom"/>
            </button>

            @include('filament.resources.dashboard.actions-start')

            <button type="button" @click="$store.filamentMenu.toggleCalendar()" class="{{ $btn }}">
                <span
                    class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity">{{ $persianDates ? 'calendar_month' : 'public' }}</span>
                <x-ui.modals.tooltip
                    :text="$persianDates ? 'تقویم شمسی — کلیک برای میلادی' : 'تقویم میلادی — کلیک برای شمسی'"
                    position="bottom"/>
            </button>

            <button type="button" x-cloak
                    @click="setTableDensity($store.tableDensityCompact ? 'comfortable' : 'compact')" class="{{ $btn }}">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity"
                      x-text="$store.tableDensityCompact ? 'density_medium' : 'density_small'"></span>
                <x-ui.modals.tooltip text="تراکم ردیف جدول‌ها (فشرده / معمول)" position="bottom"/>
            </button>

            <div class="mx-1 h-5 w-px bg-[var(--header-border-color)]/50 shrink-0"></div>

            <button type="button" @click="window.filamentMenu.share()" class="{{ $btn }}">
                <span
                    class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity">share</span>
                <x-ui.modals.tooltip text="اشتراک‌گذاری" position="bottom"/>
            </button>

            <button type="button" @click="window.filamentMenu.printPage()" class="{{ $btn }}">
                <span
                    class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity">print</span>
                <x-ui.modals.tooltip text="چاپ صفحه" position="bottom"/>
            </button>


            <button type="button" @click="window.filamentMenu.requestPiP()" class="{{ $btn }}">
                <span
                    class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity">picture_in_picture_alt</span>
                <x-ui.modals.tooltip text="پنجره شناور" position="bottom"/>
            </button>

            <button type="button" @click="$store.filamentMenu.toggleWakeLock()" class="{{ $btn }}">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity"
                      x-text="$store.filamentMenu.wakeLock ? 'bedtime' : 'light_mode'"></span>
                <x-ui.modals.tooltip text="روشن نگه‌داشتن صفحه" position="bottom"/>
            </button>

            <button type="button" @click="window.filamentMenu.clearStorage()" class="{{ $btn }}">
                <span
                    class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity">bolt</span>
                <x-ui.modals.tooltip text="پاک‌سازی کش" position="bottom"/>
            </button>

            <button type="button"
                    @click="$store.filamentMenu.resetServerCache()"
                    :disabled="$store.filamentMenu.resetting"
                    class="{{ $btn }} disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100">
                <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity"
                      :class="{ 'animate-spin': $store.filamentMenu.resetting }"
                      x-text="$store.filamentMenu.resetting ? 'progress_activity' : 'cached'"></span>
                <x-ui.modals.tooltip text="بازنشانی کش سرور" position="bottom"/>
            </button>


            <button type="button" @click="window.filamentMenu.showShortcuts()" class="{{ $btn }}">
                <span
                    class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity">keyboard_command_key</span>
                <x-ui.modals.tooltip text="میانبرها" position="bottom"/>
            </button>

            @if(\App\Filament\Resources\ReleaseRequestResource::canCreate())
                <div class="mx-1 h-5 w-px bg-[var(--header-border-color)]/50 shrink-0"></div>
                <a href="{{ \App\Filament\Resources\ReleaseRequestResource::getUrl('create') }}" class="{{ $btn }}">
                    <span
                        class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100 transition-opacity">auto_awesome</span>
                    <x-ui.modals.tooltip text="پیشنهاد ماژول" position="bottom"/>
                </a>
            @endif

            @include('filament.resources.dashboard.actions-end')

        </div>
    </div>
</div>
