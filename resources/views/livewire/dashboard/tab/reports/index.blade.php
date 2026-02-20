<div
    x-data="report()"
    class="relative w-full h-full bg-[var(--md-sys-color-background)] p-4 md:p-8 flex flex-col gap-4"
    dir="rtl"
>
    <!-- Header & Toggle -->
    <div class="flex flex-col md:flex-row justify-between items-center z-10 relative gap-4 mb-4 shrink-0">
        <h2 class="text-3xl font-extrabold tracking-tight text-[var(--md-sys-color-on-background)]">
            Reports Dashboard
        </h2>

        <div class="flex bg-[var(--md-sys-color-surface-container-high)] rounded-lg p-1 border border-[var(--md-sys-color-outline-variant)]">
            <button wire:click="toggleView('card')"
                class="p-2 rounded-md transition-all duration-300 group flex items-center justify-center"
                :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm': view === 'card', 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]': view !== 'card' }"
                title="Card View">
                <span class="material-symbols-rounded text-xl">grid_view</span>
            </button>
            <button wire:click="toggleView('list')"
                class="p-2 rounded-md transition-all duration-300 group flex items-center justify-center"
                :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm': view === 'list', 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]': view !== 'list' }"
                title="List View">
                <span class="material-symbols-rounded text-xl">view_list</span>
            </button>
        </div>
    </div>

    <!-- Content Area -->
    <div class="relative w-full h-full overflow-hidden flex-1 group/container">

        <!-- Card View (Horizontal Scroll) -->
        <div x-show="view === 'card'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="w-full h-full relative">

            <!-- Navigation Arrows (Absolute) -->
            <button @click="scrollPrev" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface)] shadow-lg flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/container:opacity-100 disabled:opacity-0 translate-x-1/2 md:translate-x-0">
                <span class="material-symbols-rounded text-3xl">chevron_right</span>
            </button>

            <button @click="scrollNext" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface)] shadow-lg flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/container:opacity-100 disabled:opacity-0 -translate-x-1/2 md:translate-x-0">
                <span class="material-symbols-rounded text-3xl">chevron_left</span>
            </button>

            <!-- Scroll Container -->
            <div x-ref="reportContainer"
                 class="flex overflow-x-auto overflow-y-hidden space-x-6 space-x-reverse pb-8 snap-x snap-mandatory scrollbar-hide h-full items-center px-4 md:px-12 relative"
                 dir="rtl">

                @foreach ($this->reports as $report)
                    <div wire:key="report-{{ $report->id }}"
                         data-report-id="{{ $report->id }}"
                         class="shrink-0 w-full max-w-sm md:w-[400px] h-[70vh] md:h-[80vh] relative group rounded-3xl overflow-hidden cursor-pointer snap-center shadow-lg border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] transition-all duration-500 transform hover:-translate-y-2 hover:shadow-xl"
                         @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                        <!-- Image -->
                        <div class="w-full h-full relative">
                            <img src="{{ $report->thumbnail }}" alt="{{ $report->title }}"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 filter brightness-90 group-hover:brightness-100">

                            <!-- Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-scrim)] via-transparent to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-300"></div>
                        </div>

                        <!-- Top Badges -->
                        <div class="absolute top-4 right-4 flex gap-2 z-10">
                             <span class="px-3 py-1 rounded-full text-xs font-bold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-outline)] shadow-sm backdrop-blur-md">
                                {{ strtoupper($report->file_type) }}
                             </span>
                        </div>

                        <!-- Content -->
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

                <!-- Load More Trigger (Sentinel) -->
                @if($this->hasMorePages)
                     <div x-ref="loadTrigger" class="shrink-0 w-24 h-full flex items-center justify-center snap-center">
                         <div class="w-10 h-10 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin"></div>
                     </div>
                @endif

                <!-- Padding for end of scroll -->
                <div class="shrink-0 w-4 snap-align-none pointer-events-none"></div>
            </div>
        </div>

        <!-- List View (Vertical Stack) -->
        <div x-show="view === 'list'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="w-full h-full overflow-y-auto pb-20 px-2 md:px-0 scrollbar-hide relative">

            <div class="space-y-4 max-w-4xl mx-auto">
                @foreach ($this->reports as $report)
                    <div wire:key="report-list-{{ $report->id }}"
                         class="flex flex-col md:flex-row items-center p-4 bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-2xl transition-all duration-300 border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-outline)] group cursor-pointer relative overflow-hidden shadow-sm hover:shadow-md"
                         @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                        <!-- Left: Thumbnail -->
                        <div class="w-full md:w-32 h-48 md:h-24 rounded-xl overflow-hidden flex-shrink-0 relative md:ml-6 mb-4 md:mb-0 bg-[var(--md-sys-color-surface-variant)]">
                            <img src="{{ $report->thumbnail }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                        </div>

                        <!-- Middle: Content -->
                        <div class="flex-grow min-w-0 text-center md:text-right w-full px-4">
                            <h4 class="text-[var(--md-sys-color-on-surface)] font-bold text-lg truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors mb-1">{{ $report->title }}</h4>
                            <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm line-clamp-2 mb-2 text-justify">{{ Str::limit(strip_tags($report->description), 200) }}</p>
                            <div class="flex items-center justify-center md:justify-start gap-3 text-xs text-[var(--md-sys-color-outline)]">
                                <span class="bg-[var(--md-sys-color-surface-container-high)] px-2 py-0.5 rounded text-[var(--md-sys-color-on-surface-variant)]">{{ $report->department->name ?? 'General' }}</span>
                                <span>{{ jdate($report->created_at)->format('Y/m/d') }}</span>
                            </div>
                        </div>

                        <!-- Right: Action -->
                        <div class="flex-shrink-0 md:mr-6 mt-4 md:mt-0">
                             <button wire:click.stop="download({{ $report->id }})"
                                     class="p-3 rounded-full bg-[var(--md-sys-color-surface-container-highest)] hover:bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-primary)] transition-all shadow-sm hover:shadow-lg">
                                <span class="material-symbols-rounded text-xl">download</span>
                             </button>
                        </div>
                    </div>
                @endforeach

                <!-- Load More Trigger (Vertical) -->
                @if($this->hasMorePages)
                    <div x-intersect.threshold.10="$wire.loadMore()" class="py-8 flex justify-center w-full">
                         <div class="w-8 h-8 border-4 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin"></div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Reusable Modal Partial -->
    @include('livewire.dashboard.tab.reports.modal')

</div>
