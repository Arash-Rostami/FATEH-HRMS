<div
    class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;"
    dir="rtl"
    x-data="ths()"
>

    <div
        class="transition-all duration-300 max-w-[88rem] mx-auto page-wrapper">
        <x-dashboard.tab.title
            icon="support_agent"
            title="تیکتینگ"
            :count="$this->totalTickets"
        />

        <x-dashboard.button.tab-selector
            :active-tab="$activeTab"
            :has-a11y="true"
            button-base-class="group relative flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl transition-all duration-200 outline-none flex-1 min-w-[140px]"
            button-active-class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/20"
            button-inactive-class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]"
            icon-base-class="material-symbols-rounded text-xl"
            icon-active-class="font-variation-fill"
            icon-inactive-class="opacity-70 group-hover:opacity-100"
            :tabs="[
                ['id' => 'rate', 'icon' => 'star','label' => 'ارزیابی','condition' => $ticketToRate ?? false],
                ['id' => 'new','icon' => 'add_box','label' => 'تیکت جدید','condition' => !($ticketToRate ?? false)],
                ['id' => 'log','icon' => 'history','label' => 'تاریخچه']
            ]"/>

        <div x-show="$wire.activeTab !== 'log'"
             class='py-4 px-6 min-h-[400px] relative index-10 overflow-x-auto rounded-2xl  bg-[var(--md-sys-color-surface)] shadow-sm'>

            <div x-show="$wire.activeTab === 'new' && !@js($ticketToRate)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="w-full">
                @include('livewire.dashboard.ths.partials.create')
            </div>
            <div x-show="$wire.activeTab === 'rate' && @js($ticketToRate)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="w-full">
                @include('livewire.dashboard.ths.partials.rate')
            </div>
        </div>
        <div x-show="$wire.activeTab === 'log'"
             x-transition:enter="animate-fade"
             class="w-full">
            @include('livewire.dashboard.ths.partials.list')
        </div>
    </div>
    @include('livewire.dashboard.ths.partials.modal')
</div>
