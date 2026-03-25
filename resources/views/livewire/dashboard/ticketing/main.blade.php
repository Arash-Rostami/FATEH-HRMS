<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate" dir="rtl" x-data="ticketing">
    <x-dashboard.tab.background />

    <x-dashboard.navbar.left />

    <main class="flex-1 w-full lg:w-[calc(100%-80px)] lg:mr-[80px] xl:w-[calc(100%-240px)] xl:ml-[240px] transition-all duration-300 min-h-screen flex flex-col pt-16 md:pt-0 relative z-10 pb-20 md:pb-0">
        <x-dashboard.header title="پشتیبانی فناوری اطلاعات" subtitle="ثبت و پیگیری درخواست‌های IT">
            <x-slot name="icon">support_agent</x-slot>
        </x-dashboard.header>

        <div class="flex-1 p-4 sm:p-6 md:p-8 lg:p-10 max-w-[1400px] w-full mx-auto animate-[fade-in-up_0.6s_ease-out]">
            <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl shadow-sm overflow-hidden flex flex-col">

                {{-- Tab Selector --}}
                <div class="flex border-b border-[var(--md-sys-color-outline-variant)]/50 overflow-x-auto hide-scrollbar bg-[var(--md-sys-color-surface-container-lowest)]" role="tablist">
                    @if($ticketToRate)
                        <button
                            wire:click="$set('activeTab', 'rate')"
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
                            wire:click="$set('activeTab', 'new')"
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
                        wire:click="$set('activeTab', 'log')"
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
                <div class="p-4 sm:p-6 lg:p-8 relative min-h-[400px]">

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
    </main>

    @include('livewire.dashboard.ticketing.partials.modal')
</div>
