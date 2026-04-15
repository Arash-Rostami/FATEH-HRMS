<div class="space-y-4">
    @foreach ($this->reports as $report)
        <div wire:key="report-list-{{ $report->id }}"
             class="flex flex-col md:flex-row items-center p-4 bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-xl transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-outline)] group cursor-pointer relative overflow-hidden shadow-sm hover:shadow-md"
             @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

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
                                class="bg-[var(--md-sys-color-surface-container-high)] px-2 py-0.5 rounded text-[var(--md-sys-color-on-surface-variant)]">{{ $report->department->name ?? 'General' }}</span>
                    <span>{{ jdate($report->created_at)->format('Y/m/d') }}</span>
                </div>
            </div>

            <div class="flex-shrink-0 md:mr-6 mt-4 md:mt-0">
                <button wire:click.stop="download({{ $report->id }})"
                        class="p-3 rounded-xl bg-[var(--md-sys-color-surface-container-highest)] hover:bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-primary)] transition-all shadow-sm hover:shadow-lg">
                    <span class="material-symbols-rounded text-xl">download</span>
                </button>
            </div>
        </div>
    @endforeach

    @if($this->hasMorePages)
        <div x-intersect.threshold.10="$wire.loadMore()" class="py-8 flex justify-center w-full">
            <div
                class="w-8 h-8 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-xl animate-spin"></div>
        </div>
    @endif
</div>
