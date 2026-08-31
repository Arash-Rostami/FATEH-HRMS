<div
    class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
   
    dir="rtl"
    x-data="reservation()"
    @confirmation-confirmed.window="$wire.call($event.detail.method, $event.detail.params)"
>
    <div class="transition-all duration-300 max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
            icon="event_available"
            title="سامانه رزرواسیون هوشمند"
            :count="count($this->resources)"
            countLabel="مورد">
            <x-slot:actions>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'reservation-legend' })"
                    title="راهنمای وضعیت رزرو"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-ui.modals.dialog name="reservation-legend" title="راهنمای وضعیت رزرو">
            @include('livewire.dashboard.reservation.legend')
        </x-ui.modals.dialog>

        @include('components.dashboard.header.focus-chip')


        <div class="w-fit z-1 bg-[var(--md-sys-color-surface)]">
            <x-ui.buttons.tab-selector
                wire:key="tab-selector-{{ $activeTab }}"
                :active-tab="$activeTab"
                :has-a11y="true"
                button-base-class="group relative flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl transition-all duration-200 outline-none flex-1 min-w-[140px]"
                button-active-class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/20"
                button-inactive-class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]"
                icon-base-class="material-symbols-rounded text-xl"
                icon-active-class="font-variation-fill"
                icon-inactive-class="opacity-70 group-hover:opacity-100"
                :tabs="$tabs"
            />
        </div>

        <div class="mb-6 animate-slide-up-fade">
            @include('livewire.dashboard.reservation.date')
        </div>

        @if($this->activeLimitUsage !== null)
            @php($usage = $this->activeLimitUsage)
            @php($pct = $usage['max'] > 0 ? min(100, (int) round($usage['count'] * 100 / $usage['max'])) : 100)
            <div class="mb-6 animate-slide-up-fade">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="material-symbols-rounded text-[16px] {{ $usage['near'] ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">stacks</span>
                    <span
                        class="text-[11px] font-semibold {{ $usage['near'] ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">
                        رزروهای فعال این ماه: {{ convertToPersian((string) $usage['count']) }} / {{ convertToPersian((string) $usage['max']) }}
                    </span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 {{ $usage['near'] ? 'bg-[var(--md-sys-color-error)]' : 'bg-[var(--md-sys-color-primary)]' }}"
                        style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @endif

        <div class="mb-8 animate-slide-up-fade flex flex-col gap-6">
            <x-ui.placeholder/>

            @includeWhen(!(\App\Enums\ResourceType::tryFrom($activeTab)?->isFullDay() ?? true), 'livewire.dashboard.reservation.time')

            @includeWhen($activeTab === 'seat' || $activeTab === 'spot','livewire.dashboard.reservation.filter')

            @include('livewire.dashboard.reservation.recurring')

        </div>

        <div class="mb-8">
            <div class="w-full h-px bg-[var(--md-sys-color-outline-variant)]" style="opacity: 0.3;"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12 w-full items-start">
            <div class="lg:col-span-8 xl:col-span-9 space-y-6 animate-slide-up-fade min-w-0"
                 style="animation-delay: 0.25s;">
                @include('livewire.dashboard.reservation.cards')
            </div>

            <div class="lg:col-span-4 xl:col-span-3 min-w-0 h-full">
                @include('livewire.dashboard.reservation.history')
            </div>
        </div>
    </div>
</div>
