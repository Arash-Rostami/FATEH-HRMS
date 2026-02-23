<div
    class="relative w-full h-full bg-[var(--md-sys-color-background)] p-4 md:p-8"
    dir="rtl"
    x-data="{
        view: 'grid', // 'grid' or 'list'
        activeReport: null,
        showModal: false,
    }"
>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8 px-2 md:px-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center shadow-sm">
                 <span class="material-symbols-rounded text-xl font-fill">bar_chart</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">گزارش‌ها و اسناد</h2>
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5">آرشیو فایل‌های سازمانی</p>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="bg-[var(--md-sys-color-surface-container-high)] p-1 rounded-xl flex gap-1 border border-[var(--md-sys-color-outline-variant)]/30">
            <button @click="view = 'grid'"
                    :class="view === 'grid' ? 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]'"
                    class="p-2 rounded-lg transition-all duration-200">
                <span class="material-symbols-rounded text-xl">grid_view</span>
            </button>
            <button @click="view = 'list'"
                    :class="view === 'list' ? 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-primary)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]'"
                    class="p-2 rounded-lg transition-all duration-200">
                <span class="material-symbols-rounded text-xl">view_list</span>
            </button>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="relative w-full h-[calc(100%-80px)]">

        <!-- Grid View (Horizontal Scroll) -->
        <div x-show="view === 'grid'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute inset-0 group/container">

            <div class="absolute top-1/2 left-0 right-0 h-px bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"></div>

            <!-- Navigation Buttons -->
            <button @click="scrollPrev" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 backdrop-blur-md text-[var(--md-sys-color-on-surface-variant)] shadow-sm flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all opacity-0 group-hover/container:opacity-100 disabled:opacity-0 translate-x-1/2 md:translate-x-0">
                <span class="material-symbols-rounded text-2xl">chevron_right</span>
            </button>

            <button @click="scrollNext" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-variant)]/80 backdrop-blur-md text-[var(--md-sys-color-on-surface-variant)] shadow-sm flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all opacity-0 group-hover/container:opacity-100 disabled:opacity-0 -translate-x-1/2 md:translate-x-0">
                <span class="material-symbols-rounded text-2xl">chevron_left</span>
            </button>

            <!-- Scroll Container -->
            <div x-ref="reportContainer"
                 class="flex overflow-x-auto overflow-y-hidden snap-x snap-mandatory scrollbar-hide w-full h-full items-center gap-6 md:gap-12 px-[5%] md:px-12 relative z-10"
                 dir="rtl">

                @foreach ($this->reports as $report)
                    <div wire:key="report-{{ $report->id }}"
                         class="shrink-0 w-full max-w-sm md:w-[400px] h-[70vh] md:h-[80vh] relative group rounded-2xl overflow-hidden cursor-pointer snap-center shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] transition-all duration-300 transform hover:-translate-y-2 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]"
                         @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                         {{-- Active/Hover Stripe --}}
                        <div class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] z-20 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-center shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)]"></div>

                        <!-- Image -->
                        <div class="w-full h-3/5 relative bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                            <img src="{{ $report->thumbnail }}" alt="{{ $report->title }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <!-- Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-[var(--md-sys-color-surface)]/20 to-transparent opacity-90"></div>

                            <!-- File Type Badge -->
                             <div class="absolute top-4 right-4 z-10">
                                 <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border border-[var(--md-sys-color-tertiary)]/20 shadow-sm uppercase tracking-wider">
                                    {{ strtoupper($report->file_type) }}
                                 </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="absolute inset-x-0 bottom-0 top-[50%] flex flex-col p-6 text-right z-10 bg-[var(--md-sys-color-surface)]">
                            <div class="mb-auto">
                                <h3 class="text-[var(--md-sys-color-on-surface)] text-lg font-bold leading-tight mb-2 group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                                    {{ $report->title }}
                                </h3>
                                <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm line-clamp-3 leading-[2] text-justify font-normal">
                                    {{ Str::limit(strip_tags($report->description), 150) }}
                                </p>
                            </div>

                            <div class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/20 flex justify-between items-center">
                                <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] font-mono opacity-80">{{ jdate($report->created_at)->format('Y/m/d') }}</span>
                                <button wire:click.stop="download({{ $report->id }})"
                                        class="flex items-center gap-2 text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] bg-[var(--md-sys-color-primary-container)]/30 hover:bg-[var(--md-sys-color-primary)] px-3 py-1.5 rounded-full text-xs font-bold transition-all hover:shadow-md">
                                    <span>دانلود</span>
                                    <span class="material-symbols-rounded text-base">download</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Load More Trigger (Sentinel) -->
                @if($this->hasMorePages)
                    <div x-ref="loadTrigger" class="shrink-0 w-24 h-full flex items-center justify-center snap-center opacity-60">
                        <x-dashboard.loader.spinner/>
                    </div>
                @endif

                <!-- Padding for end of scroll -->
                <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none"></div>
            </div>
        </div>

        <!-- List View (Vertical Stack) -->
        <div x-show="view === 'list'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="w-full h-full overflow-y-auto pb-20 px-2 md:px-0 scrollbar-hide relative custom-scrollbar">

            <div class="space-y-4 max-w-4xl mx-auto pt-2">
                @foreach ($this->reports as $report)
                    <div wire:key="report-list-{{ $report->id }}"
                         class="flex flex-col md:flex-row items-center p-4 bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-low)] rounded-2xl transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-primary)]/30 group cursor-pointer relative overflow-hidden shadow-sm hover:shadow-md"
                         @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                        <!-- Left: Thumbnail -->
                        <div class="w-full md:w-32 h-48 md:h-24 rounded-xl overflow-hidden flex-shrink-0 relative md:ml-6 mb-4 md:mb-0 bg-[var(--md-sys-color-surface-variant)] shadow-sm">
                            <img src="{{ $report->thumbnail }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                        </div>

                        <!-- Middle: Content -->
                        <div class="flex-grow min-w-0 text-center md:text-right w-full px-4">
                            <h4 class="text-[var(--md-sys-color-on-surface)] font-bold text-sm md:text-base truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors mb-1.5">{{ $report->title }}</h4>
                            <p class="text-[var(--md-sys-color-on-surface-variant)] text-xs md:text-sm line-clamp-2 mb-3 text-justify leading-relaxed">{{ Str::limit(strip_tags($report->description), 200) }}</p>
                            <div class="flex items-center justify-center md:justify-start gap-3 text-[10px] text-[var(--md-sys-color-outline)]">
                                <span class="bg-[var(--md-sys-color-surface-container-high)] px-2 py-0.5 rounded-md text-[var(--md-sys-color-on-surface-variant)] font-bold">{{ $report->department->name ?? 'General' }}</span>
                                <span class="font-mono opacity-80">{{ jdate($report->created_at)->format('Y/m/d') }}</span>
                            </div>
                        </div>

                        <!-- Right: Action -->
                        <div class="flex-shrink-0 md:mr-6 mt-4 md:mt-0">
                            <button wire:click.stop="download({{ $report->id }})"
                                    class="p-2.5 rounded-xl bg-[var(--md-sys-color-primary-container)]/30 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all shadow-sm">
                                <span class="material-symbols-rounded text-xl">download</span>
                            </button>
                        </div>
                    </div>
                @endforeach

                <!-- Load More Trigger (Vertical) -->
                @if($this->hasMorePages)
                    <div x-intersect.threshold.10="$wire.loadMore()" class="py-8 flex justify-center w-full opacity-60">
                        <x-dashboard.loader.spinner/>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Reusable Modal Partial -->
    @include('livewire.dashboard.tab.reports.partials.modal')

    <!-- Toast Component -->
    <x-dashboard.modal.toast />
</div>
