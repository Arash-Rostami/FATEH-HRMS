<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col gap-4"
     x-data="status(@js($view))"
     x-on:keydown.escape.window="openId = null"
     wire:ignore.self>

    <div dir="rtl">
        <x-ui.title
            icon="badge"
            title="وضعیت همکاران"
            :count="array_sum($this->stats)"
            countLabel="نفر">
            <x-slot:actions>
                <x-ui.buttons.view-toggle module="status" :modes="[
                    ['value' => 'grid', 'icon' => 'grid_view', 'title' => 'نمای کارتی'],
                    ['value' => 'chart', 'icon' => 'account_tree', 'title' => 'نمای ساختار سازمانی'],
                ]"/>

                @if(count($this->todaysOccasions))
                    <button
                        type="button"
                        x-on:click="$refs.occasions?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                        title="مشاهده مناسبت‌های امروز"
                        class="flex items-center gap-1 px-2.5 h-8 rounded-lg text-[11px] font-bold bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] hover:brightness-95 transition-all animate-pulse-slow"
                    >
                        <span class="material-symbols-rounded text-sm">celebration</span>
                        {{ count($this->todaysOccasions) }}
                    </button>
                @endif
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'status-skill-legend' })"
                    title="راهنمای وضعیت و ساختار سازمانی"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>
    </div>

    <x-ui.modals.dialog name="status-skill-legend" title="راهنمای وضعیت و ساختار سازمانی">
        @include('livewire.dashboard.tab.status.legend')
    </x-ui.modals.dialog>

    @include('livewire.dashboard.tab.status.occasions')

    @include('livewire.dashboard.tab.status.filters')

    <div x-show="view === 'grid'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full">
        @include('livewire.dashboard.tab.status.grid')
    </div>

    @unless(tenantOff('status:chart'))
        <div wire:key="status-view-chart"
             x-show="view === 'chart'"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="w-full">
            @include('livewire.dashboard.tab.status.chart')
        </div>
    @endunless

    @include('livewire.dashboard.tab.status.about-me')
</div>