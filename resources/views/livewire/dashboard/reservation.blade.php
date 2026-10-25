@use('App\Enums\ResourceType')
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
                <x-ui.buttons.form variant="ghost" icon="groups" wire:click="openDaily" title="رزروهای همکاران" aria-label="رزروهای همکاران" class="!h-8 !w-8 !p-0 !rounded-lg" />
                <x-ui.buttons.view-toggle
                    module="reservation"
                    responsive
                    state="view"
                    action="toggleView"
                    :modes="[
                        ['value' => 'classic', 'icon' => 'grid_view', 'title' => 'نمای کلاسیک'],
                        ['value' => 'avantgarde', 'icon' => 'dashboard_customize', 'title' => 'نمای آوانگارد'],
                    ]"
                />
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

        @include('livewire.dashboard.reservation.daily')

        @include('components.dashboard.header.focus-chip', ['open' => $deckOnly ? null : $open])


        @if($view === 'avantgarde' && ! tenantOff('reservation:avantgarde'))
            <div wire:key="reservation-view-avantgarde" class="contents">
                @include('livewire.dashboard.reservation.avantgarde')
            </div>
        @else
            <div wire:key="reservation-view-classic" class="contents">
                @if(empty($tabs))
                    <div wire:key="reservation-classic-no-permission" class="contents">
                        <x-ui.empty icon="lock" title="دسترسی رزرو ندارید" description="مجوز رزرو هیچ نوع منبعی برای شما فعال نشده است. با مدیر سیستم تماس بگیرید." variant="list" />
                    </div>
                @else
                <div wire:key="reservation-classic-booking" class="contents">
                <div class="w-fit mx-auto md:mx-0 z-1">
                    <x-ui.buttons.tab-selector
                        :active-tab="$activeTab"
                        :has-a11y="true"
                        :tabs="$tabs"
                    />
                </div>

                @include('livewire.dashboard.reservation.limits')
                @include('livewire.dashboard.reservation.rules')

                <div class="lg:hidden mb-4">
                    <x-ui.buttons.form variant="ghost" icon="history" x-on:click="document.getElementById('reservation-history')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="!h-8 !px-2 text-[11px] font-bold">
                        رفتن به تاریخچه من
                        <span class="material-symbols-rounded text-[16px]">arrow_downward</span>
                    </x-ui.buttons.form>
                </div>

                <div class="mb-6 animate-slide-up-fade">
                    @include('livewire.dashboard.reservation.date')
                </div>

                <div class="mb-8 animate-slide-up-fade flex flex-col gap-6">
                    @includeWhen(!(ResourceType::tryFrom($activeTab)?->isFullDay() ?? true), 'livewire.dashboard.reservation.time')

                    @if(count($this->facets) > 0)
                        <div wire:key="resv-facets-panel-classic" class="contents">
                            <x-ui.forms.filters
                                placeholder="جستجوی منابع..."
                                searchModel="resourceSearch"
                                filterTitle="فیلتر منابع"
                                activeCondition="Object.keys(this.$wire.get('facetFilters') || {}).length > 0"
                                clearAction="this.$wire.call('resetFilters')"
                            >
                                @include('livewire.dashboard.reservation.filter')
                            </x-ui.forms.filters>
                        </div>
                    @endif

                    @include('livewire.dashboard.reservation.recurring')

                </div>

                <div class="mb-8">
                    <div class="w-full h-px bg-[var(--md-sys-color-outline-variant)]" style="opacity: 0.3;"></div>
                </div>
                </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12 w-full items-start">
                    <div class="lg:col-span-8 space-y-6 animate-slide-up-fade min-w-0"
                         style="animation-delay: 0.25s;">
                        @includeWhen(! empty($tabs), 'livewire.dashboard.reservation.cards')
                    </div>

                    <div id="reservation-history" class="lg:col-span-4 min-w-0 h-full scroll-mt-20">
                        @include('livewire.dashboard.reservation.history')
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
