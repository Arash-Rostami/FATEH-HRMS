<div
    x-data="faq"
    wire:ignore.self
    @record-focus.window="if ($event.detail.type === 'faqs') { active = $event.detail.id; view = 'card' }"

    class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] flex flex-col overflow-hidden"
    dir="rtl"
>

    <div class="pb-0 shrink-0">
        <x-ui.title icon="help" title="پرسش‌های متداول" :count="$this->totalFaqs" countLabel="سوال">
            <x-slot:actions>
                <x-ui.buttons.view-toggle/>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'faqs-legend' })"
                    title="راهنمای پرسش‌های متداول"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-ui.modals.dialog name="faqs-legend" title="راهنمای پرسش‌های متداول">
            @include('livewire.dashboard.tab.faqs.legend')
        </x-ui.modals.dialog>

        @include('components.dashboard.header.focus-chip')
    </div>

    @include('livewire.dashboard.tab.faqs.filters')

    <div
        class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar relative bg-[var(--md-sys-color-surface-container-lowest)]">
        <div wire:loading.delay
             class="absolute inset-0 bg-[var(--md-sys-color-surface-container-lowest)]/50 z-50 flex items-center justify-center">
            <x-ui.loaders.spin-badge text="در حال فیلتر کردن..."/>
        </div>

        <div x-show="view === 'card'" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-start">
            @forelse($this->faqs as $faq)
                @include('livewire.dashboard.tab.faqs.cards')
            @empty
                <div class="col-span-full">
                    @include('livewire.dashboard.tab.faqs.empty')
                </div>
            @endforelse
        </div>

        <div x-show="view === 'list'" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-3">
            @include('livewire.dashboard.tab.faqs.list')
        </div>

        @if($this->faqs->hasMorePages())
            <div class="flex justify-center py-6 pb-12">
                <x-ui.buttons.load-more
                    action="loadMore"
                    text="بارگذاری بیشتر"
                    loading-text="در حال دریافت..."
                    icon="expand_more"
                    wire:island="faqs"
                    class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
                />
            </div>
        @endif
    </div>
</div>
