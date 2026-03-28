<div
    x-data="{
    active: null,
    toggle(id) { this.active = (this.active === id) ? null : id }
     }"
    class="animate-fade relative w-full max-w-[88rem] mx-auto h-full flex flex-col overflow-hidden"
    dir="rtl"
>

    <div class="pb-0 shrink-0">
        <x-dashboard.tab.title icon="help" title="پرسش‌های متداول" :count="$this->totalFaqs" countLabel="سوال"/>
    </div>

    @include('livewire.dashboard.tab.faqs.partials.modal')

    <div
        class="flex-1 overflow-y-auto p-4 md:p-8 space-y-4 custom-scrollbar relative bg-[var(--md-sys-color-surface-container-lowest)]">
        <div wire:loading.delay
             class="absolute inset-0 bg-[var(--md-sys-color-surface-container-lowest)]/50 z-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--md-sys-color-primary)]"></div>
        </div>

        @island('faqs')
        @forelse($this->faqs as $faq)
            <div
                x-data="{ expanded: false }"
                x-init="$watch('active', value => expanded = value === {{ $faq->id }})"
                class="group flex flex-col rounded-2xl overflow-hidden transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-primary)]/30 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] bg-[var(--md-sys-color-surface)]  w-full shadow-sm"
                :class="expanded ? 'ring-1 ring-[var(--md-sys-color-primary)] shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]' : ''"
            >
                <button
                    @click="toggle({{ $faq->id }})"
                    class="w-full flex items-center justify-between p-4 text-right transition-colors duration-200"
                    :class="expanded ? 'bg-[var(--md-sys-color-surface-container-high)]' : 'bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-low)]'"
                >
                    <div class="flex items-start gap-4">
                        <div class="mt-0.5 shrink-0 p-2 rounded-lg transition-colors duration-300"
                             :class="expanded ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] group-hover:bg-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)]'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                 class="w-5 h-5">
                                <path fill-rule="evenodd"
                                      d="M4.804 21.644A6.707 6.707 0 006 21.75a6.721 6.721 0 003.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9 0-5.03-4.428-9-9.75-9s-9.75 3.97-9.75 9c0 2.409 1.025 4.587 2.674 6.192.232.226.277.428.254.543a3.73 3.73 0 01-.814 1.686.75.75 0 00.44 1.223zM8.25 10.875a1.125 1.125 0 100 2.25 1.125 1.125 0 000-2.25zM10.875 12a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zm4.875-1.125a1.125 1.125 0 100 2.25 1.125 1.125 0 000-2.25z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="flex flex-col gap-1.5 text-right">
                            <span
                                class="text-base font-bold text-[var(--md-sys-color-on-surface)] leading-snug transition-colors group-hover:text-[var(--md-sys-color-primary)]">
                                {{ superClean($faq->question, 300) }}
                            </span>
                            <div
                                class="flex flex-wrap items-center gap-2 text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                                <span
                                    class="bg-[var(--md-sys-color-surface-variant)] px-2 py-0.5 rounded-md font-medium">{{ $faq->category }}</span>
                                @if($faq->department)
                                    <span class="text-[var(--md-sys-color-outline)]">•</span>
                                    <span class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                             class="w-3 h-3">
                                            <path fill-rule="evenodd"
                                                  d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                        {{ $faq->department->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <span
                        class="shrink-0 transition-transform duration-300 text-[var(--md-sys-color-primary)] p-1 rounded-full hover:bg-[var(--md-sys-color-primary-container)]"
                        :class="expanded ? 'rotate-180 bg-[var(--md-sys-color-primary-container)]' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                             stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </span>
                </button>

                <div
                    x-show="expanded"
                    x-collapse
                    class="overflow-hidden bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]/50"
                >
                    <div class="p-5 pr-[4.5rem] pl-6 relative">
                        <div
                            class="absolute right-6 top-6 bottom-6 w-0.5 bg-[var(--md-sys-color-outline-variant)]/50 rounded-full"></div>
                        <div
                            class="prose prose-sm prose-p:text-[var(--md-sys-color-on-surface)] prose-a:text-[var(--md-sys-color-primary)] max-w-none text-sm leading-7 text-justify"
                            dir="rtl">
                            {!! str_replace('<a ', '<a target="_blank" class="hover:underline font-medium decoration-[var(--md-sys-color-primary)]/30 underline-offset-4 hover:decoration-[var(--md-sys-color-primary)] transition-all" ', $faq->answer) !!}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="flex flex-col items-center justify-center h-64 text-[var(--md-sys-color-on-surface-variant)] opacity-60">
                <div class="p-4 rounded-full bg-[var(--md-sys-color-surface-container-high)] mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                <p class="text-base font-medium">هیچ سوالی یافت نشد</p>
                <p class="text-xs mt-1 opacity-70">لطفا عبارت دیگری را جستجو کنید</p>
            </div>
        @endforelse

        @if($this->faqs->hasMorePages())
            <div class="flex justify-center py-6 pb-12">
                <x-dashboard.button.load-more
                    action="loadMore"
                    text="بارگذاری بیشتر"
                    loading-text="در حال دریافت..."
                    icon="expand_more"
                    wire:island="faqs"
                    class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
                />
            </div>
        @endif
        @endisland
    </div>
</div>
