@props(['translatePage' => false])

<footer
    class="w-full mt-auto relative z-30"
    x-data="{
        year: new Date().getFullYear(),
        showCredits: false
    }"
>
    <!-- Glassmorphism Container -->
    <div class="relative overflow-hidden border-t border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)]/80 backdrop-blur-xl">

        <!-- Ambient Glow Effects -->
        <div class="absolute top-0 left-1/4 w-32 h-32 bg-[var(--md-sys-color-primary)]/5 rounded-full blur-3xl -translate-y-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/4 w-32 h-32 bg-[var(--md-sys-color-tertiary)]/5 rounded-full blur-3xl translate-y-1/2 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 py-4 md:py-5">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs md:text-sm">

                <!-- Left: Signature -->
                <div class="flex items-center gap-2 order-2 md:order-1">
                    <button
                        @click="window.open('https://time-gr.com/cv', '_blank')"
                        class="group flex items-center gap-1.5 px-3 py-1.5 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/5 transition-all duration-300"
                    >
                        <span class="text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                            Crafted with
                        </span>
                        <span class="material-symbols-rounded text-[16px] text-red-500 animate-pulse">favorite</span>
                        <span class="text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                            by Arash Rostami
                        </span>
                    </button>
                </div>

                <!-- Center: Translate Toggle -->
                <div class="order-1 md:order-2">
                    <x-dashboard.toggle-google :translatePage="$translatePage" />
                </div>

                <!-- Right: Copyright -->
                <div class="text-[var(--md-sys-color-on-surface-variant)] order-3 text-center md:text-right font-mono opacity-80">
                    <span dir="ltr">© <span x-text="year"></span> - {{ config('app.name') }} V{{ config('app.version') }}</span>
                </div>

            </div>
        </div>
    </div>

    <!-- Occasion Components (Birthday/Anniversary) -->
    <x-user.occasion.main />

</footer>
