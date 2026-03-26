<div class="relative w-full h-full p-4 md:p-8 overflow-y-auto scrollbar-hide"
     x-data="ticketing"
     dir="rtl">
    <div class="max-w-[88rem] mx-auto">

        <x-dashboard.tab.title icon="support_agent" title="پشتیبانی فناوری اطلاعات"/>

        <div class="w-full flex flex-col gap-5">
            <div class="relative w-full overflow-hidden rounded-2xl bg-[var(--md-sys-color-surface)] shadow-sm border border-[var(--md-sys-color-outline-variant)]/50">
                <div class="absolute inset-0 opacity-[0.03]"
                     style="background-image: radial-gradient(circle at 2px 2px, var(--md-sys-color-on-surface) 1px, transparent 0); background-size: 24px 24px;"></div>

                <div class="relative flex flex-col w-full min-h-[500px]">

                    {{-- Tab Selector --}}
                    <div class="flex border-b border-[var(--md-sys-color-outline-variant)]/50 overflow-x-auto hide-scrollbar bg-[var(--md-sys-color-surface-container-lowest)] w-full" role="tablist">
                        @if($ticketToRate)
                            <button
                                wire:click="switchTab('rate')"
                                role="tab"
                                :aria-selected="$wire.activeTab === 'rate'"
                                class="flex items-center justify-center gap-2 px-6 py-4 text-sm font-medium transition-all relative flex-1 min-w-[140px]"
                                :class="$wire.activeTab === 'rate'
                                    ? 'text-[var(--md-sys-color-primary)]'
                                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-low)] hover:text-[var(--md-sys-color-on-surface)]'"
                            >
                                <span class="material-symbols-rounded text-lg" :class="$wire.activeTab === 'rate' ? 'font-variation-fill' : ''">star</span>
                                ارزیابی
                                <div x-show="$wire.activeTab === 'rate'" x-transition class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
                            </button>
                        @else
                            <button
                                wire:click="switchTab('new')"
                                role="tab"
                                :aria-selected="$wire.activeTab === 'new'"
                                class="flex items-center justify-center gap-2 px-6 py-4 text-sm font-medium transition-all relative flex-1 min-w-[140px]"
                                :class="$wire.activeTab === 'new'
                                    ? 'text-[var(--md-sys-color-primary)]'
                                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-low)] hover:text-[var(--md-sys-color-on-surface)]'"
                            >
                                <span class="material-symbols-rounded text-lg" :class="$wire.activeTab === 'new' ? 'font-variation-fill' : ''">add_box</span>
                                تیکت جدید
                                <div x-show="$wire.activeTab === 'new'" x-transition class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
                            </button>
                        @endif

                        <button
                            wire:click="switchTab('log')"
                            role="tab"
                            :aria-selected="$wire.activeTab === 'log'"
                            class="flex items-center justify-center gap-2 px-6 py-4 text-sm font-medium transition-all relative flex-1 min-w-[140px]"
                            :class="$wire.activeTab === 'log'
                                ? 'text-[var(--md-sys-color-primary)]'
                                : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-low)] hover:text-[var(--md-sys-color-on-surface)]'"
                        >
                            <span class="material-symbols-rounded text-lg" :class="$wire.activeTab === 'log' ? 'font-variation-fill' : ''">history</span>
                            تاریخچه درخواست‌ها
                            <div x-show="$wire.activeTab === 'log'" x-transition class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
                        </button>
                    </div>

                    {{-- Content Area --}}
                    <div class="p-4 sm:p-6 lg:p-8 relative flex-1 w-full bg-transparent">
                        <div x-show="$wire.activeTab === 'new' && !@js($ticketToRate)"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="w-full">
                            @include('livewire.dashboard.ticketing.partials.create')
                        </div>

                        <div x-show="$wire.activeTab === 'rate' && @js($ticketToRate)"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="w-full">
                            @include('livewire.dashboard.ticketing.partials.rate')
                        </div>

                        <div x-show="$wire.activeTab === 'log'"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="w-full">
                            @include('livewire.dashboard.ticketing.partials.list')
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Reusable Slideover Modal Integration --}}
    @include('livewire.dashboard.ticketing.partials.details-slideover')
</div>
