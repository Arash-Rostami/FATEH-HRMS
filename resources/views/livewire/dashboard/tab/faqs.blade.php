<div
        x-data="faq"
        @record-focus.window="if ($event.detail.type === 'faqs') active = $event.detail.id"

        class="animate-fade relative w-full max-w-[88rem] mx-auto mx-auto max-h-[calc(100svh-10rem)] flex flex-col overflow-hidden"
        dir="rtl"
>

    <div class="pb-0 shrink-0">
        <x-ui.title icon="help" title="پرسش‌های متداول" :count="$this->totalFaqs" countLabel="سوال"/>

        @include('components.dashboard.header.focus-chip')
    </div>

    @include('livewire.dashboard.tab.faqs.filters')

    <div
            class="flex-1 overflow-y-auto p-4 md:p-8 space-y-4 custom-scrollbar relative bg-[var(--md-sys-color-surface-container-lowest)]">
        <div wire:loading.delay
             class="absolute inset-0 bg-[var(--md-sys-color-surface-container-lowest)]/50 z-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--md-sys-color-primary)]"></div>
        </div>

        @forelse($this->faqs as $faq)
            @include('livewire.dashboard.tab.faqs.accordion')
        @empty
            @include('livewire.dashboard.tab.faqs.empty')
        @endforelse

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
