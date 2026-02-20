<div class="min-h-screen p-4 md:p-8 relative overflow-hidden text-white"
    x-data="{ view: @entangle('view'), showModal: false, activeReport: null }">

    <!-- Header & Toggle -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 z-10 relative gap-4">
        <h2 class="text-3xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">
            Reports Dashboard
        </h2>

        <div class="flex bg-white/10 backdrop-blur-md rounded-lg p-1 border border-white/10">
            <button @click="$wire.toggleView('card')"
                :class="{ 'bg-blue-600 shadow-lg': view === 'card', 'hover:bg-white/5': view !== 'card' }"
                class="p-2 rounded-md transition-all duration-300 group" title="Card View">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            </button>
            <button @click="$wire.toggleView('list')"
                :class="{ 'bg-blue-600 shadow-lg': view === 'list', 'hover:bg-white/5': view !== 'list' }"
                class="p-2 rounded-md transition-all duration-300 group" title="List View">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Content Area -->
    <div class="relative z-10 w-full">

        <!-- Card View (Horizontal Scroll) -->
        <div x-show="view === 'card'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="flex flex-col space-y-4">

            <!-- Scroll Container -->
            <div class="flex overflow-x-auto space-x-6 pb-8 snap-x scroll-smooth hide-scrollbar px-2" dir="ltr">
                @forelse ($reports as $report)
                    <div class="min-w-[300px] md:min-w-[360px] h-[480px] relative group rounded-3xl overflow-hidden cursor-pointer snap-center shadow-2xl border border-white/10 hover:border-blue-500/50 transition-all duration-500 transform hover:-translate-y-2 bg-gray-900"
                         @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                        <!-- Image -->
                        <img src="{{ $report->thumbnail }}" alt="{{ $report->title }}"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 filter brightness-75 group-hover:brightness-100">

                        <!-- Glassmorphism Overlay (Gradient) -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent opacity-90 group-hover:opacity-100 transition-opacity duration-300"></div>

                        <!-- Top Badges -->
                        <div class="absolute top-4 left-4 flex gap-2">
                             <span class="px-2 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm text-white border border-white/10">
                                {{ strtoupper($report->file_type) }}
                             </span>
                        </div>

                        <!-- Content -->
                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-left" dir="rtl">
                            <h3 class="text-white text-2xl font-bold translate-y-4 group-hover:translate-y-0 transition-transform duration-500 delay-75 drop-shadow-lg leading-tight mb-2">
                                {{ $report->title }}
                            </h3>

                            <div class="h-0 group-hover:h-auto overflow-hidden transition-all duration-500 ease-in-out opacity-0 group-hover:opacity-100">
                                 <p class="text-gray-300 text-sm line-clamp-3 mb-4">
                                    {{ Str::limit(strip_tags($report->description), 120) }}
                                 </p>

                                 <div class="flex justify-between items-center mt-2">
                                     <span class="text-xs text-gray-400 font-mono">{{ jdate($report->created_at)->format('Y/m/d') }}</span>
                                     <button wire:click.stop="download({{ $report->id }})"
                                             class="flex items-center gap-1 text-blue-400 hover:text-blue-300 text-sm font-bold uppercase tracking-wider transition-colors">
                                        <span>دانلود</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                     </button>
                                 </div>
                            </div>

                            <!-- Initial glimpse of date/meta when not hovered -->
                             <div class="group-hover:hidden transition-opacity duration-300 text-gray-400 text-xs mt-1">
                                {{ jdate($report->created_at)->format('Y/m/d') }} • {{ $report->department->name ?? 'عمومی' }}
                             </div>
                        </div>
                    </div>
                @empty
                    <div class="w-full flex flex-col items-center justify-center py-20 text-gray-400">
                        <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-lg">هیچ گزارشی یافت نشد</p>
                    </div>
                @endforelse

                <!-- Load More Trigger -->
                @if($reports->hasMorePages())
                     <div x-intersect="$wire.loadMore()" class="min-w-[100px] flex items-center justify-center">
                         <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                     </div>
                @endif
            </div>
        </div>

        <!-- List View (Vertical Stack) -->
        <div x-show="view === 'list'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-4 max-w-4xl mx-auto pb-20">

            @foreach ($reports as $report)
                <div class="flex flex-col md:flex-row items-center p-4 bg-white/5 hover:bg-white/10 backdrop-blur-md rounded-2xl transition-all duration-300 border border-white/5 hover:border-white/20 group cursor-pointer relative overflow-hidden"
                     @click="activeReport = {{ json_encode($report->only(['id', 'title', 'description', 'file_type']) + ['created_at_formatted' => jdate($report->created_at)->format('Y/m/d')]) }}; activeReport.thumbnail = '{{ $report->thumbnail }}'; showModal = true">

                    <!-- Left: Thumbnail -->
                    <div class="w-full md:w-24 h-40 md:h-24 rounded-xl overflow-hidden flex-shrink-0 relative md:ml-6 mb-4 md:mb-0 bg-gray-800">
                        <img src="{{ $report->thumbnail }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                    </div>

                    <!-- Middle: Content -->
                    <div class="flex-grow min-w-0 text-center md:text-right w-full">
                        <h4 class="text-white font-bold text-lg truncate group-hover:text-blue-400 transition-colors mb-1">{{ $report->title }}</h4>
                        <p class="text-gray-400 text-sm line-clamp-2 mb-2">{{ Str::limit(strip_tags($report->description), 150) }}</p>
                        <div class="flex items-center justify-center md:justify-start gap-3 text-xs text-gray-500">
                            <span class="bg-white/10 px-2 py-0.5 rounded text-gray-300">{{ $report->department->name ?? 'General' }}</span>
                            <span>{{ jdate($report->created_at)->format('Y/m/d') }}</span>
                        </div>
                    </div>

                    <!-- Right: Action -->
                    <div class="flex-shrink-0 md:mr-6 mt-4 md:mt-0">
                         <button wire:click.stop="download({{ $report->id }})"
                                 class="p-2 rounded-full bg-white/5 hover:bg-blue-600 text-gray-400 hover:text-white transition-all shadow-lg hover:shadow-blue-500/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                         </button>
                    </div>
                </div>
            @endforeach

            <!-- Load More Trigger (Vertical) -->
            @if($reports->hasMorePages())
                <div x-intersect="$wire.loadMore()" class="py-8 flex justify-center w-full">
                     <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                </div>
            @endif
        </div>

    </div>

    <!-- Modal for Full Details -->
    <div x-show="showModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         @click.self="showModal = false">

        <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto relative shadow-2xl"
             x-show="showModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4">

             <!-- Close Button -->
             <button @click="showModal = false" class="absolute top-4 right-4 z-10 p-2 rounded-full bg-black/50 hover:bg-white/20 text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
             </button>

             <template x-if="activeReport">
                 <div>
                    <!-- Hero Image -->
                    <div class="h-64 w-full relative">
                        <img :src="activeReport.thumbnail" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent"></div>
                        <div class="absolute bottom-4 right-4 left-4" dir="rtl">
                             <h2 class="text-2xl md:text-3xl font-bold text-white drop-shadow-md" x-text="activeReport.title"></h2>
                             <p class="text-gray-300 text-sm mt-1" x-text="activeReport.created_at_formatted"></p>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 md:p-8 space-y-6" dir="rtl">
                        <div class="prose prose-invert max-w-none text-gray-300 leading-relaxed text-justify" x-html="activeReport.description"></div>

                        <div class="flex justify-center pt-4 border-t border-white/10">
                            <button @click="$wire.download(activeReport.id)"
                                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg hover:shadow-blue-500/30 transform hover:-translate-y-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>دانلود فایل کامل</span>
                            </button>
                        </div>
                    </div>
                 </div>
             </template>
        </div>
    </div>

    <!-- Decorative Background blobs -->
    <div class="fixed top-20 right-0 w-96 h-96 bg-blue-600/20 rounded-full mix-blend-screen filter blur-3xl opacity-20 pointer-events-none animate-blob"></div>
    <div class="fixed bottom-0 left-0 w-96 h-96 bg-purple-600/20 rounded-full mix-blend-screen filter blur-3xl opacity-20 pointer-events-none animate-blob animation-delay-2000"></div>
</div>
