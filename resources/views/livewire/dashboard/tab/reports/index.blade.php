<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col"
     dir="rtl"
     x-data="{
        showModal: false,
        activeReport: null,
        view: @entangle('view'),
        init() {
            const enforceListOnMobile = () => {
                if (window.innerWidth < 768 && this.view !== 'list') {
                    this.view = 'list';
                    $wire.toggleView('list');
                }
            };
            enforceListOnMobile();
            window.addEventListener('resize', enforceListOnMobile);
        },
        scrollNext() {
             this.$refs.reportContainer.scrollBy({ left: -350, behavior: 'smooth' });
        },
        scrollPrev() {
             this.$refs.reportContainer.scrollBy({ left: 350, behavior: 'smooth' });
        }
     }">

    <div>
        <x-dashboard.tab.title icon="show_chart" title="گزارشات" :count="$this->totalReports" countLabel="گزارش" />
    </div>

    <div class="px-4 md:px-12 pt-2 md:pt-4 flex items-center justify-between mb-2 md:mb-4 relative z-10">
        <h2 class="text-[var(--md-sys-color-on-surface)] text-xl md:text-2xl font-black tracking-tight flex items-center gap-3">
            <span class="bg-[var(--md-sys-color-primary)] w-2 h-8 rounded-full"></span>
            لیست گزارش‌ها
        </h2>

        <div class="hidden md:flex bg-[var(--md-sys-color-surface-container-high)] p-1 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm">
            <button @click="view = 'card'; $wire.toggleView('card')"
                    :class="view === 'card' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                    class="p-2 rounded-lg transition-all duration-300 flex items-center justify-center w-10 h-10">
                <span class="material-symbols-rounded">grid_view</span>
            </button>
            <button @click="view = 'list'; $wire.toggleView('list')"
                    :class="view === 'list' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                    class="p-2 rounded-lg transition-all duration-300 flex items-center justify-center w-10 h-10">
                <span class="material-symbols-rounded">view_list</span>
            </button>
        </div>
    </div>

    <div x-show="view === 'card'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full relative group/container hidden md:block"
         style="height: clamp(420px, calc(100svh - 200px), 800px);">

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
                     class="shrink-0 w-[80vw] sm:w-[340px] md:w-[380px] h-[90%] relative group rounded-3xl overflow-hidden cursor-pointer snap-center shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] transition-all duration-300 transform hover:-translate-y-2 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]"
                     @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                    <div class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] z-20 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-center shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)]"></div>

                    <div class="w-full h-full relative">
                        <img src="{{ $report->thumbnail }}"
                             alt="{{ $report->title }}"
                             class="absolute inset-0 w-full h-auto object-cover transition-transform duration-700 group-hover:scale-110 filter brightness-90 group-hover:brightness-100">

                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors duration-300"></div>
                    </div>

                    <div class="absolute top-4 right-4 flex gap-2 z-10">
                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-outline)] shadow-sm">
                            {{ strtoupper($report->file_type) }}
                        </span>
                    </div>

                    <div class="absolute inset-0 flex flex-col justify-end p-6 text-right z-10">
                        <h3 class="text-[var(--md-sys-color-on-surface)] text-2xl font-bold translate-y-4 group-hover:translate-y-0 transition-transform duration-500 delay-75 drop-shadow-md leading-tight mb-2 mix-blend-screen">
                            {{ $report->title }}
                        </h3>

                        <div class="grid grid-rows-[0fr] group-hover:grid-rows-[1fr] transition-[grid-template-rows] duration-500 ease-in-out">
                            <div class="overflow-hidden">
                                <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm line-clamp-3 mb-4 text-justify leading-relaxed font-light">
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
                <div x-ref="loadTrigger" class="shrink-0 w-24 h-full flex items-center justify-center snap-center">
                    <div class="w-10 h-10 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-xl animate-spin"></div>
                </div>
            @endif

            <div class="shrink-0 w-4 snap-align-none pointer-events-none"></div>
        </div>
    </div>

    <div x-show="view === 'list'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full px-4 md:px-12 pb-8">

        <div class="space-y-4">
            @foreach ($this->reports as $report)
                <div wire:key="report-list-{{ $report->id }}"
                     class="flex flex-col md:flex-row items-center p-4 bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-xl transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-outline)] group cursor-pointer relative overflow-hidden shadow-sm hover:shadow-md"
                     @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                    <div class="w-full md:w-32 h-48 md:h-24 rounded-xl overflow-hidden flex-shrink-0 relative md:ml-6 mb-4 md:mb-0">
                        <img src="{{ $report->thumbnail }}"
                             class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors duration-300"></div>
                    </div>

                    <div class="flex-grow min-w-0 text-center md:text-right w-full px-4">
                        <h4 class="text-[var(--md-sys-color-on-surface)] font-bold text-lg truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors mb-1">{{ $report->title }}</h4>
                        <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm line-clamp-2 mb-2 text-justify">{{ Str::limit(strip_tags($report->description), 200) }}</p>
                        <div class="flex items-center justify-center md:justify-start gap-3 text-xs text-[var(--md-sys-color-outline)]">
                            <span class="bg-[var(--md-sys-color-surface-container-high)] px-2 py-0.5 rounded text-[var(--md-sys-color-on-surface-variant)]">{{ $report->department->name ?? 'General' }}</span>
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
                    <div class="w-8 h-8 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-xl animate-spin"></div>
                </div>
            @endif
        </div>
    </div>

    @include('livewire.dashboard.tab.reports.partials.modal')

    <x-dashboard.modal.toast />
</div>
