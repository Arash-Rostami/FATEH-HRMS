@props(['title' => '', 'description' => '', 'imageContent' => null])

<div class="flex items-center justify-center w-full px-4">
    <div class="glass-panel w-full max-w-[1000px] h-[700px] rounded-[2.5rem] relative overflow-hidden isolate shadow-2xl ring-1 ring-white/10 flex flex-col lg:flex-row transition-all duration-500 ease-out hover:shadow-[0_20px_50px_-12px_rgba(0,0,0,0.25)] bg-[var(--md-sys-color-surface)]/20 backdrop-blur-md">

        {{-- Left Panel (Image/Branding) - Hidden on mobile, visible on LG --}}
        <div class="hidden lg:flex w-1/2 h-full relative overflow-hidden bg-gradient-to-br from-[var(--md-sys-color-primary-container)]/20 via-[var(--md-sys-color-surface)]/10 to-[var(--md-sys-color-tertiary-container)]/20 backdrop-blur-3xl items-center justify-center group">

            {{-- Abstract Shapes/Blobs for "Image" effect --}}
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48ZmlsdGVyIGlkPSJuIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iMC42NSIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNuKSIgb3BhY2l0eT0iMC40Ii8+PC9zdmc+')] opacity-20 mix-blend-overlay"></div>

            <div class="absolute -top-20 -left-20 w-80 h-80 bg-[var(--md-sys-color-primary)]/20 rounded-full blur-[100px] group-hover:bg-[var(--md-sys-color-primary)]/30 transition-colors duration-700"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-[var(--md-sys-color-tertiary)]/20 rounded-full blur-[120px] group-hover:bg-[var(--md-sys-color-tertiary)]/30 transition-colors duration-700"></div>

            {{-- Interactive decorative element --}}
            <div class="absolute inset-0 bg-gradient-to-tr from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>

            {{-- Content Overlay --}}
            <div class="relative z-10 p-12 text-center flex flex-col items-center justify-center h-full gap-8">
                @if($imageContent)
                    {{ $imageContent }}
                @else
                    {{-- Default Branding --}}
                    <div class="relative">
                        <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/20 blur-2xl rounded-full"></div>
                        <div class="w-28 h-28 rounded-[2rem] bg-white/10 backdrop-blur-md border border-white/20 shadow-2xl flex items-center justify-center relative z-10 transform transition-transform duration-500 group-hover:scale-105 group-hover:rotate-3">
                            <span class="material-symbols-rounded text-7xl text-[var(--md-sys-color-primary)] drop-shadow-sm">token</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-4xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight font-display drop-shadow-sm">
                            {{ config('app.name', 'ERP System') }}
                        </h2>
                        <div class="h-1 w-12 bg-[var(--md-sys-color-primary)]/50 rounded-full mx-auto"></div>
                        <p class="text-[var(--md-sys-color-on-surface-variant)] text-lg font-medium tracking-wide">
                            Enterprise Resource Planning
                        </p>
                    </div>

                    <div class="mt-8 flex gap-3 opacity-60">
                        <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-on-surface-variant)] animate-bounce" style="animation-delay: 0s"></div>
                        <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-on-surface-variant)] animate-bounce" style="animation-delay: 0.2s"></div>
                        <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-on-surface-variant)] animate-bounce" style="animation-delay: 0.4s"></div>
                    </div>
                @endif
            </div>

            {{-- Divider Line --}}
            <div class="absolute right-0 top-10 bottom-10 w-[1px] bg-gradient-to-b from-transparent via-[var(--md-sys-color-outline-variant)]/20 to-transparent"></div>
        </div>

        {{-- Right Panel (Form) --}}
        <div class="w-full lg:w-1/2 h-full flex flex-col relative bg-[var(--md-sys-color-surface)]/40 backdrop-blur-xl">
             {{-- Mobile Header (Visible only on small screens) --}}
            <div class="lg:hidden pt-8 px-6 pb-2 text-center flex flex-col items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-[var(--md-sys-color-primary-container)]/30 border border-[var(--md-sys-color-outline-variant)]/20 shadow-lg flex items-center justify-center">
                    <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-primary)]">token</span>
                </div>
                <h2 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">{{ config('app.name') }}</h2>
            </div>

            {{-- Scrollable Content Area --}}
            <div class="flex-1 overflow-y-auto overflow-x-hidden p-8 md:p-10 scrollbar-thin scrollbar-thumb-[var(--md-sys-color-outline-variant)]/20 scrollbar-track-transparent hover:scrollbar-thumb-[var(--md-sys-color-outline-variant)]/40 transition-colors">
                <div class="flex flex-col h-full justify-center min-h-min">
                    {{-- Form Header --}}
                    <div class="mb-8 text-center lg:text-start space-y-2">
                        <h1 class="text-3xl md:text-4xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight font-display">
                            {{ $title }}
                        </h1>
                        @if($description)
                        <p class="text-[var(--md-sys-color-on-surface-variant)] text-base font-medium leading-relaxed max-w-md mx-auto lg:mx-0">
                            {{ $description }}
                        </p>
                        @endif
                    </div>

                    {{-- Slot --}}
                    <div class="w-full relative z-10 space-y-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
