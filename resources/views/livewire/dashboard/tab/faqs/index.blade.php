<div
    x-data="faq"
    class="relative w-full h-full flex flex-col bg-[var(--md-sys-color-background)] overflow-hidden"
    dir="rtl"
>
    {{-- Header Section --}}
    <div class="shrink-0 p-4 space-y-4 z-10 bg-[var(--md-sys-color-surface)]/80 backdrop-blur-md border-b border-[var(--md-sys-color-outline-variant)]">

        {{-- Search Bar --}}
        <div class="relative group">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[var(--md-sys-color-on-surface-variant)]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                class="block w-full p-4 pr-10 text-sm text-[var(--md-sys-color-on-surface)] bg-[var(--md-sys-color-surface-container-high)] border-none rounded-2xl focus:ring-2 focus:ring-[var(--md-sys-color-primary)] placeholder-[var(--md-sys-color-on-surface-variant)] transition-all duration-300 shadow-sm hover:shadow-md"
                placeholder="جستجو در سوالات..."
            >
        </div>

        {{-- Filters Container --}}
        <div class="flex flex-col gap-3">

            {{-- Category Chips --}}
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1 mask-linear-fade">
                <button
                    wire:click="filterByCategory('all')"
                    class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 border border-transparent"
                    :class="$wire.selectedCategory === null ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
                >
                    همه
                </button>
                @foreach($this->categories as $category)
                    <button
                        wire:click="filterByCategory('{{ $category }}')"
                        class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 border border-transparent"
                        :class="$wire.selectedCategory === '{{ $category }}' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
                    >
                        {{ $category }}
                    </button>
                @endforeach
            </div>

            {{-- Department Chips --}}
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                @foreach($this->departments as $dept)
                    <div class="group relative shrink-0">
                        <button
                            wire:click="filterByDepartment({{ $dept->id }})"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 border"
                            :class="$wire.selectedDepartment === {{ $dept->id }} ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-[var(--md-sys-color-secondary)]' : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)]'"
                        >
                            <span class="truncate max-w-[120px]">{{ $dept->name }}</span>
                        </button>

                        {{-- Tooltip --}}
                        @if($dept->description)
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-xs rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 pointer-events-none shadow-xl text-center">
                                {{ $dept->description }}
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-[var(--md-sys-color-inverse-surface)]"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Content Section --}}
    <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar relative">
        {{-- Loading Indicator Overlay --}}
        <div wire:loading.delay class="absolute inset-0 bg-[var(--md-sys-color-background)]/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl">
             <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--md-sys-color-primary)]"></div>
        </div>

        @forelse($this->faqs as $faq)
            <div
                x-data="{ expanded: false }"
                x-init="$watch('active', value => expanded = value === {{ $faq->id }})"
                class="group bg-[var(--md-sys-color-surface-container-low)] rounded-xl overflow-hidden transition-all duration-300 border border-transparent hover:border-[var(--md-sys-color-outline-variant)] hover:shadow-md"
                :class="expanded ? '!bg-[var(--md-sys-color-surface-container)] shadow-lg ring-1 ring-[var(--md-sys-color-primary)]/20' : ''"
            >
                {{-- Question Header --}}
                <button
                    @click="toggle({{ $faq->id }})"
                    class="w-full flex items-center justify-between p-4 text-right"
                >
                    <div class="flex items-start gap-3">
                         <span class="mt-1 shrink-0 p-1.5 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 006 21.75a6.721 6.721 0 003.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9 0-5.03-4.428-9-9.75-9s-9.75 3.97-9.75 9c0 2.409 1.025 4.587 2.674 6.192.232.226.277.428.254.543a3.73 3.73 0 01-.814 1.686.75.75 0 00.44 1.223zM8.25 10.875a1.125 1.125 0 100 2.25 1.125 1.125 0 000-2.25zM10.875 12a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0zm4.875-1.125a1.125 1.125 0 100 2.25 1.125 1.125 0 000-2.25z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div class="flex flex-col gap-1 text-right">
                            <span class="text-sm font-semibold text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors line-clamp-2">
                                {!! $faq->question !!}
                            </span>
                            <div class="flex items-center gap-2 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">
                                <span class="bg-[var(--md-sys-color-surface-variant)] px-1.5 py-0.5 rounded">{{ $faq->category }}</span>
                                @if($faq->department)
                                    <span>•</span>
                                    <span>{{ $faq->department->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Icon --}}
                    <span class="shrink-0 transition-transform duration-300 text-[var(--md-sys-color-primary)]" :class="expanded ? 'rotate-180' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </span>
                </button>

                {{-- Answer Body --}}
                <div
                    x-show="expanded"
                    x-collapse
                    class="overflow-hidden"
                >
                    <div class="p-4 pt-0 pr-14 pl-4">
                        <div class="h-px w-full bg-[var(--md-sys-color-outline-variant)] mb-3 opacity-50"></div>
                        <div class="prose prose-sm prose-p:text-[var(--md-sys-color-on-surface-variant)] prose-a:text-[var(--md-sys-color-primary)] max-w-none text-sm leading-relaxed text-right" dir="rtl">
                             {!! str_replace('<a ', '<a target="_blank" class="hover:underline text-[var(--md-sys-color-primary)]" ', $faq->answer) !!}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-64 text-[var(--md-sys-color-on-surface-variant)] opacity-60">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                </svg>
                <p class="text-sm">هیچ سوالی یافت نشد</p>
            </div>
        @endforelse

        @if($this->faqs->hasMorePages())
            <div class="flex justify-center py-4">
                 <button wire:click="loadMore" class="text-sm text-[var(--md-sys-color-primary)] hover:underline font-medium px-4 py-2 rounded-lg hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                    بارگذاری بیشتر...
                </button>
            </div>
        @endif
    </div>
</div>
