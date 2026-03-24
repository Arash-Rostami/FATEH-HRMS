@props(['translatePage' => false])

<footer
    id="footer"
    class="w-full mt-auto relative z-30"
    dir="ltr"
    x-data="{
        get now() {
            const d = new Date();
            return {year:  d.getFullYear(),month: d.toLocaleDateString('en-US', { month: 'long' })
            };
        }
    }"
>
    {{-- Background Container with Overflow Hidden --}}
    <div
        class="absolute inset-0 overflow-hidden bg-[var(--md-sys-color-surface)] rounded-b-2xl -z-10 pointer-events-none">
        <div
            class="absolute top-0 left-1/4 w-40 h-40 rounded-full blur-3xl -translate-y-1/2 pointer-events-none opacity-[0.06]"
            style="background: var(--md-sys-color-primary)"></div>
        <div
            class="absolute bottom-0 right-1/4 w-40 h-40 rounded-full blur-3xl translate-y-1/2 pointer-events-none opacity-[0.05]"
            style="background: var(--md-sys-color-tertiary)"></div>
    </div>

    {{-- Content Container --}}
    <div class="relative z-10 max-w-7xl mx-auto px-1 sm:px-4 py-1 sm:py-0">
        <div class="flex flex-row items-center justify-around md:justify-between w-full">

            <div class="group relative flex items-stretch rounded-xl cursor-pointer scale-[0.70] sm:scale-[0.85] origin-left
                        hover:border-[var(--md-sys-color-primary)]/30
                        hover:shadow-[0_2px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]
                        transition-all duration-300 overflow-visible"
                 @click="window.open('https://time-gr.com/cv', '_blank')">

                <div class="flex items-center justify-center px-3 rounded-l-xl
                            bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]
                            border-r border-[var(--md-sys-color-primary)]/15
                            transition-all duration-300
                            group-hover:bg-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)]">
                    <span
                        class="material-symbols-rounded text-[15px] font-fill transition-transform duration-300 group-hover:rotate-12">hub</span>
                </div>

                <div class="relative flex flex-col justify-center px-3 py-1 gap-0.5 rounded-r-xl
                            bg-[var(--md-sys-color-surface-variant)]/30
                            group-hover:bg-[var(--md-sys-color-primary)]/5
                            transition-colors duration-300">

                    <div class="relative overflow-hidden h-[18px] flex items-center">
                        <span class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)] absolute whitespace-nowrap
                                     transition-all duration-300
                                     group-hover:opacity-0 group-hover:-translate-y-3">
                            {{ config('app.name') }}
                        </span>
                        <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] absolute whitespace-nowrap
                                     opacity-0 translate-y-3 flex items-center gap-1.5
                                     transition-all duration-300
                                     group-hover:opacity-100 group-hover:translate-y-0">
                            <span
                                class="material-symbols-rounded text-[13px] leading-none text-red-400 font-fill flex-shrink-0">favorite</span>
                             {{ config('app.developer') }}
                        </span>
                        <span class="text-[11px] opacity-0 pointer-events-none flex items-center gap-1.5">
                            <span class="text-[13px]">·</span>{{ config('app.developer') }}
                        </span>
                    </div>

                    <span
                        class="text-[10px] font-mono text-[var(--md-sys-color-on-surface-variant)] opacity-55 whitespace-nowrap"
                        x-text="`${now.month} ${now.year}`"></span>

                    <x-dashboard.tooltip text="Developer" position="top"/>
                </div>
            </div>

            <div class="scale-[0.70] sm:scale-[0.9] origin-center">
                <x-dashboard.footer.google/>
            </div>

            <div class="flex items-center gap-1 sm:gap-2 scale-[0.70] sm:scale-100 origin-right">
                <span title="last update: {{ config('app.last_update') }}"
                      class="relative group inline-flex items-center px-2 py-1 rounded-lg scale-125 sm:scale-[1.5] origin-right sm:origin-center
                             bg-[var(--md-sys-color-surface-variant)]/50 cursor-help
                             text-[10px] font-bold font-mono text-[var(--md-sys-color-on-surface-variant)] tracking-wide">
                    v:{{ config('app.version') }}
                </span>
                <span
                    class="text-[11px] font-mono text-[var(--md-sys-color-on-surface-variant)] opacity-60 ml-2 sm:ml-0 whitespace-nowrap">
                    © <span x-text="now.year"></span>
                </span>
            </div>

        </div>
    </div>
</footer>
