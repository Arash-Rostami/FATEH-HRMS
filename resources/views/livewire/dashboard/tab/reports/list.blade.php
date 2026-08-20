<div class="space-y-4">
    @forelse ($this->reports as $report)
        <div wire:key="report-list-{{ $report->id }}"
             class="flex flex-col md:flex-row items-center p-4 bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-xl transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-outline)] group cursor-pointer relative overflow-hidden shadow-sm hover:shadow-md"
             @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' =>  toJalali($report->created_at, 'j F Y'), 'report_date_formatted' => $report->report_date ? toJalali($report->report_date, 'j F Y') : null]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

            <div
                class="w-full md:w-32 h-48 md:h-24 rounded-xl overflow-hidden flex-shrink-0 relative md:ml-6 mb-4 md:mb-0">
                <img src="{{ $report->thumbnail }}"
                     class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                <div
                    class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors duration-300"></div>
            </div>

            <div class="flex-grow min-w-0 text-center md:text-right w-full px-4">
                <h4 class="text-[var(--md-sys-color-on-surface)] font-bold text-lg truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors mb-1">{{ $report->title }}</h4>
                <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm line-clamp-2 mb-2 text-justify">{{ Str::limit(strip_tags($report->description), 200) }}</p>
                <div
                    class="flex items-center justify-center md:justify-start gap-3 text-xs text-[var(--md-sys-color-outline)]">
                            <span
                                title="{{ $report->department?->tooltipLabel() }}"
                                class="bg-[var(--md-sys-color-surface-container-high)] px-2 py-0.5 rounded text-[var(--md-sys-color-on-surface-variant)]">{{ $report->department?->displayLabel() ?? 'General' }}</span>
                    @if($report->pinned)
                        <span class="flex items-center gap-1 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] px-2 py-0.5 rounded-md text-[10px] font-bold">
                            <span class="material-symbols-rounded text-[12px] leading-none">bookmark</span>
                            سنجاق
                        </span>
                    @endif
                    <span dir="rtl">{{  toJalali($report->report_date ?? $report->created_at, 'j F Y') }}</span>
                    @if($report->updated_at && $report->updated_at->gt($report->created_at))
                        <span class="flex items-center gap-1 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] px-2 py-0.5 rounded-md text-[10px] font-medium">
                            <span class="material-symbols-rounded text-[12px] leading-none">edit</span>
                            به‌روز شده
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex-shrink-0 md:mr-6 mt-4 md:mt-0">
                <button wire:click.stop="download({{ $report->id }})"
                        class="p-3 rounded-xl bg-[var(--md-sys-color-surface-container-highest)] hover:bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-primary)] transition-all shadow-sm hover:shadow-lg">
                    <span class="material-symbols-rounded text-xl">download</span>
                </button>
            </div>
        </div>
    @empty
        @if($this->search !== '' || $this->activeFilter !== 'all')
            <x-ui.empty icon="search_off" title="نتیجه‌ای یافت نشد" description="با فیلترهای انتخابی هیچ گزارشی مطابقت ندارد." variant="filtered" />
        @else
            <x-ui.empty icon="folder_open" title="هیچ گزارشی یافت نشد" description="هنوز هیچ گزارشی بارگذاری نشده است." variant="list" />
        @endif
    @endforelse

    @if($this->hasMorePages)
        <div class="py-8 flex justify-center w-full">
            <x-ui.buttons.load-more
                    action="loadMore"
                    text="بارگذاری بیشتر"
                    loading-text="در حال دریافت..."
                    icon="expand_more"
                    wire:island="reports"
                    class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
            />
        </div>
    @endif
</div>
