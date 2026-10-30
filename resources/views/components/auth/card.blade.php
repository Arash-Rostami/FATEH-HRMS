@props(['title' => '', 'description' => '', 'imageContent' => null])

<div class="flex w-full max-h-screen items-center justify-center bg-transparent p-0 animate-slide-left">
    <div class="glass-panel isolate relative flex h-auto w-full max-w-[1000px] flex-col overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/10 transition-all duration-500 ease-out hover:shadow-[0_20px_50px_-12px_rgba(0,0,0,0.25)] lg:h-[700px] lg:flex-row">

        {{-- Brand Panel (Desktop) --}}
        <div class="group relative hidden h-full w-1/4 items-center justify-center overflow-hidden bg-gradient-to-br from-[var(--md-sys-color-primary-container)]/20 via-[var(--md-sys-color-surface)]/10 to-[var(--md-sys-color-tertiary-container)]/20 lg:flex">
            <div class="absolute inset-0 opacity-20 mix-blend-overlay bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48ZmlsdGVyIGlkPSJuIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iMC42NSIgbnVtT2N0YXZlcz0iMyIgc3RpdGNoVGlsZXM9InN0aXRjaCIvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNuKSIgb3BhY2l0eT0iMC40Ii8+PC9zdmc+')]"></div>

            <div class="absolute -left-20 -top-20 h-80 w-80 rounded-full bg-[var(--md-sys-color-primary)]/20 transition-colors duration-700 group-hover:bg-[var(--md-sys-color-primary)]/30"></div>
            <div class="absolute inset-0 bg-gradient-to-tr from-white/5 to-transparent opacity-0 transition-opacity duration-700 group-hover:opacity-100"></div>

            <div class="relative z-10 flex h-full flex-col items-center justify-center gap-8 p-12 text-center">
                <div class="relative transition-transform duration-700 ease-in-out group-hover:scale-110">
                    <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] opacity-30"></div>
                    <div class="relative z-10 flex h-20 w-20 items-center justify-center rounded-lg">
                        <span class="material-symbols-rounded !text-4xl text-[var(--md-sys-color-on-surface)]">fingerprint</span>
                    </div>
                </div>

                <div class="relative z-10 space-y-4 text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)] drop-shadow-sm" title="{{ config('app.name_en') }}">
                        {{ config('app.name') }}
                    </h2>
                    <p class="mx-auto max-w-[250px] text-lg font-medium leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                        {{ config('app.slogan') }}
                    </p>
                </div>
            </div>

            <div class="absolute bottom-10 right-0 top-10 w-px bg-gradient-to-b from-transparent via-[var(--md-sys-color-outline-variant)]/20 to-transparent"></div>
        </div>

        {{-- Form Panel --}}
        <div class="relative flex h-full w-full flex-col bg-[var(--md-sys-color-surface)]/40 lg:w-3/4">
            {{-- Mobile Header --}}
            <div class="flex flex-col items-center gap-4 px-6 pb-2 pt-8 text-center lg:hidden">
                <div class="relative z-10 flex h-20 w-20 items-center justify-center rounded-full border border-white/20 bg-gradient-to-br from-white/10 to-white/5 shadow-2xl">
                    <span class="material-symbols-rounded !text-4xl text-[var(--md-sys-color-on-surface)] drop-shadow-2xl">fingerprint</span>
                </div>
            </div>

            <div class="flex-1 overflow-x-hidden overflow-y-auto p-8 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-[var(--md-sys-color-outline-variant)]/20 transition-colors hover:scrollbar-thumb-[var(--md-sys-color-outline-variant)]/40 md:p-10">
                <div class="flex h-full min-h-min flex-col justify-center">
                    <div class="mb-8 space-y-2 text-center lg:text-start">
                        <h1 class="font-display text-3xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)] md:text-4xl">
                            {{ $title }}
                        </h1>
                        @if ($description)
                            <p class="mx-auto max-w-md text-base font-medium leading-relaxed text-[var(--md-sys-color-on-surface-variant)] lg:mx-0">
                                {{ $description }}
                            </p>
                        @endif
                    </div>

                    <div class="relative z-10 w-full space-y-6">
                        {{ $slot }}
                    </div>

                    <div class="secureTip absolute bottom-3 left-2 animate-slide-in-left animate-delay-1000 text-xs font-mono text-gray-500 md:left-5" dir="ltr">
                        <small>Secure. Your information is fully protected.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
