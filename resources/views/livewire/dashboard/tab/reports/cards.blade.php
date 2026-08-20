@if($this->reports->isNotEmpty())

    <div class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"></div>

    <div
        x-show="showTimeline"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-20"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-20"
        x-transition:leave-end="opacity-0"
        class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"
    ></div>

    <div
        x-ref="timeline"
        class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-4 md:px-[5%] md:pr-[10%] md:pl-4 z-10"
        style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
    >
        <button @click="scrollPrev"
                class="absolute right-0 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface)] shadow-lg flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/container:opacity-100 md:right-4">
            <span class="material-symbols-rounded text-3xl">chevron_right</span>
        </button>

        <button @click="scrollNext"
                class="absolute left-0 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface)] shadow-lg flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/container:opacity-100 md:left-4">
            <span class="material-symbols-rounded text-3xl">chevron_left</span>
        </button>

        <div
            x-ref="reportContainer"
            class="w-full h-full flex flex-col md:flex-row overflow-y-auto md:overflow-y-visible md:overflow-x-visible md:snap-x md:snap-mandatory gap-6 scrollbar-hide items-center md:items-stretch transition-all duration-500 ease-in-out"
            :class="showTimeline ? 'md:gap-18 md:py-16' : 'md:gap-12 md:py-8 md:p-4'"
        >
            @foreach ($this->reports as $report)
                <div
                    x-data="{ imageViewer: false }"
                    wire:key="report-{{ $report->id }}"
                    data-rf="reports-{{ $report->id }}"
                    data-report-id="{{ $report->id }}"
                    class="shrink-0 w-[80vw] sm:w-[340px] md:w-[380px] h-[90%] relative group rounded-3xl overflow-hidden cursor-pointer snap-center shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] transition-all duration-500 flex flex-col hover:shadow-[0_20px_40px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]"
                    :class="{
                        'md:scale-[1.15] z-30': activeId == {{ $report->id }},
                        'md:scale-95 z-10': activeId != {{ $report->id }}
                    }"
                    @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => toJalali($report->created_at, 'j F Y'), 'report_date_formatted' => $report->report_date ? toJalali($report->report_date, 'j F Y') : null]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true"
                >
                    {{-- Timeline dot --}}
                    <div
                        x-show="showTimeline"
                        x-transition:enter="transition ease-out duration-300 delay-100"
                        x-transition:enter-start="opacity-0 scale-90 translate-x-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-x-1/2"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-x-1/2"
                        x-transition:leave-end="opacity-0 scale-90 translate-x-4"
                        class="absolute top-1/2 -right-10 z-40 hidden md:flex flex-col items-center justify-center -translate-y-1/2 translate-x-1/2 pointer-events-none"
                    >
                        <div
                            class="absolute bottom-12 whitespace-nowrap px-2.5 py-1.5 rounded-lg bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0"
                            :class="activeId == {{ $report->id }} ? '!opacity-100 !translate-y-0' : ''"
                        >
                            <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)]">
                                {{ toJalali($report->created_at, 'j F Y') }}
                            </span>
                            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-variant)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"></div>
                        </div>
                        <div
                            class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-all duration-500"
                            :class="activeId == {{ $report->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                        </div>
                        <div
                            class="absolute top-12 whitespace-nowrap opacity-60 group-hover:opacity-100 transition-opacity duration-300"
                            :class="activeId == {{ $report->id }} ? '!opacity-100' : ''"
                        >
                            <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                                {{ strtoupper($report->file_type) }}
                            </span>
                        </div>
                    </div>

                    {{-- Top accent bar --}}
                    <div class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] z-20 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-center shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)]"></div>

                    {{-- Image: top 60% --}}
                    <div
                        class="relative w-full h-[60%] overflow-hidden cursor-zoom-in"
                        @click.stop="imageViewer = true"
                    >
                        <img
                            src="{{ $report->thumbnail }}"
                            alt="{{ $report->title }}"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                        >

                        {{-- File type badge --}}
                        <div class="absolute top-3 right-3 z-10">
                            <span class="px-3 py-1 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-outline)] shadow-sm">
                                {{ strtoupper($report->file_type) }}
                            </span>
                        </div>

                        {{-- Zoom button --}}
                        <button
                            @click.stop="imageViewer = true"
                            class="absolute left-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-md border border-white/10 bg-black/30 text-white/90 shadow-lg opacity-0 group-hover:opacity-100 transition-all hover:bg-black/50 hover:text-white"
                        >
                            <span class="material-symbols-rounded text-[18px]">open_in_full</span>
                        </button>
                    </div>

                    {{-- Solid info panel: bottom 40% --}}
                    <div class="flex flex-col justify-between flex-1 px-5 py-4 bg-[var(--md-sys-color-surface)] text-right border-t border-[var(--md-sys-color-outline-variant)]/30">
                        <div>
                            <h3 class="text-[var(--md-sys-color-on-surface)] text-lg font-bold leading-snug mb-1 line-clamp-2">
                                {{ $report->title }}
                            </h3>
                            <p class="text-[var(--md-sys-color-on-surface-variant)] text-xs line-clamp-2 leading-relaxed font-light opacity-80">
                                {{ Str::limit(strip_tags($report->description), 100) }}
                            </p>
                        </div>

                        <div class="flex justify-between items-center pt-3 border-t border-[var(--md-sys-color-outline-variant)]/20 mt-3">
                            <div class="flex items-center gap-2">
                                @if($report->pinned)
                                    <span class="flex items-center gap-1 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] px-2 py-0.5 rounded-md text-[10px] font-bold">
                                        <span class="material-symbols-rounded text-[12px] leading-none">bookmark</span>
                                        سنجاق
                                    </span>
                                @endif
                                <span dir="rtl" class="text-xs text-[var(--md-sys-color-on-surface-variant)] opacity-70">
                                    {{ toJalali($report->report_date ?? $report->created_at, 'j F Y') }}
                                </span>
                                @if($report->updated_at && $report->updated_at->gt($report->created_at))
                                    <span class="flex items-center gap-1 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] px-2 py-0.5 rounded-md text-[10px] font-medium">
                                        <span class="material-symbols-rounded text-[12px] leading-none">edit</span>
                                        به‌روز شده
                                    </span>
                                @endif
                            </div>
                            <button
                                wire:click.stop="download({{ $report->id }})"
                                class="flex items-center gap-2 text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary-container)] bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-primary-container)] px-3 py-1.5 rounded-lg text-sm font-bold transition-all shadow-sm"
                            >
                                <span>دانلود</span>
                                <span class="material-symbols-rounded text-sm">download</span>
                            </button>
                        </div>
                    </div>

                    {{-- Fullscreen image viewer --}}
                    <template x-teleport="body">
                        <div
                            x-cloak
                            x-show="imageViewer"
                            x-transition.opacity.duration.200ms
                            class="fixed inset-0 z-[99999] bg-[var(--md-sys-color-primary)] animate-lightbox-in"
                            @keydown.escape.window="imageViewer = false"
                            @click.self="imageViewer = false"
                        >
                            <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-8">
                                <div class="relative w-full max-w-[min(96vw,1600px)]">
                                    <img
                                        src="{{ $report->thumbnail }}"
                                        alt="{{ $report->title }}"
                                        class="max-h-[92vh] w-full select-none object-contain rounded-xl"
                                    >
                                    <button
                                        @click="imageViewer = false"
                                        class="absolute right-3 top-3 flex h-11 w-11 items-center justify-center rounded-xl bg-black/55 text-white shadow-lg transition hover:scale-105 hover:bg-black/75"
                                    >
                                        <span class="material-symbols-rounded">close</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endforeach

            @if($this->hasMorePages)
                <div x-ref="loadTriggerCard"
                     wire:key="loader-{{ $this->reports->count() }}"
                     class="shrink-0 w-full md:w-24 h-full snap-center flex items-center justify-center opacity-60">
                    <x-ui.loaders.spinner/>
                </div>
            @endif

            <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none h-1"></div>
        </div>
    </div>

@else
    @if($this->search !== '' || $this->activeFilter !== 'all')
        <x-ui.empty icon="search_off" title="نتیجه‌ای یافت نشد" description="با فیلترهای انتخابی هیچ گزارشی مطابقت ندارد." variant="filtered" :fill="true" />
    @else
        <x-ui.empty icon="folder_open" title="هیچ گزارشی یافت نشد" description="هنوز هیچ گزارشی بارگذاری نشده است." variant="list" :fill="true" />
    @endif
@endif
