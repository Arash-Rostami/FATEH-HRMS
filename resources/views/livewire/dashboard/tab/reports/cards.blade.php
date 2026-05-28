<button @click="scrollPrev"
        class="absolute right-0 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface)] shadow-lg flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/container:opacity-100 md:right-4">
    <span class="material-symbols-rounded text-3xl">chevron_right</span>
</button>

<button @click="scrollNext"
        class="absolute left-0 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface)] shadow-lg flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/container:opacity-100 md:left-4">
    <span class="material-symbols-rounded text-3xl">chevron_left</span>
</button>

<div x-ref="reportContainer"
     class="flex h-full overflow-x-auto overflow-y-hidden gap-x-6 pb-4 snap-x snap-mandatory scrollbar-hide items-center px-4 md:px-14"
     dir="rtl">

    @foreach ($this->reports as $report)
        <div wire:key="report-{{ $report->id }}"
             data-report-id="{{ $report->id }}"
             class="shrink-0 w-[80vw] sm:w-[340px] md:w-[380px] h-[90%] relative group rounded-3xl overflow-hidden cursor-pointer snap-center shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] transition-all duration-500 transform hover:-translate-y-2 hover:shadow-[0_20px_40px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]"
             @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] z-20 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-center shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)]"></div>

            <div class="w-full h-full relative">
                <img src="{{ $report->thumbnail }}"
                     alt="{{ $report->title }}"
                     class="absolute inset-0 w-full h-auto object-cover transition-transform duration-700 group-hover:scale-110">
            </div>

            <div class="absolute top-4 right-4 flex gap-2 z-10">
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-outline)] shadow-sm">
                    {{ strtoupper($report->file_type) }}
                </span>
            </div>

            <div class="absolute inset-0 flex flex-col justify-end p-6 text-right z-10">
                <h3 class="text-[var(--md-sys-color-on-surface)] text-2xl font-bold translate-y-4 group-hover:translate-y-0 transition-transform duration-500 delay-75 drop-shadow-[0_2px_4px_rgba(0,0,0,0.5)] leading-tight mb-2">
                    {{ $report->title }}
                </h3>

                <div class="grid grid-rows-[0fr] group-hover:grid-rows-[1fr] transition-[grid-template-rows] duration-500 ease-in-out">
                    <div class="overflow-hidden">
                        <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm line-clamp-3 mb-4 text-justify leading-relaxed font-light drop-shadow-sm">
                            {{ Str::limit(strip_tags($report->description), 150) }}
                        </p>

                        <div class="flex justify-between items-center mt-2 pt-4 border-t border-[var(--md-sys-color-outline-variant)]">
                            <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] font-mono opacity-80">{{ jdate($report->created_at)->format('Y/m/d') }}</span>
                            <button wire:click.stop="download({{ $report->id }})"
                                    class="flex items-center gap-2 text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary-container)] bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-primary-container)] px-3 py-1.5 rounded-lg text-sm font-bold transition-all shadow-sm">
                                <span>دانلود</span>
                                <span class="material-symbols-rounded text-sm">download</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if($this->hasMorePages)
        <div x-ref="loadTrigger"
             class="shrink-0 w-24 h-full flex items-center justify-center snap-center">
            <div class="w-10 h-10 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-xl animate-spin"></div>
        </div>
    @endif

    <div class="shrink-0 w-4 snap-align-none pointer-events-none"></div>
</div>
