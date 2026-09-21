@if(auth()->check() && config('app.ai_assistant_access') === true && config('services.ai_assistant.url'))
    @php
        $aiAssistantBaseSrc = rtrim(config('services.ai_assistant.url'), '/') . '/?user=' . \Illuminate\Support\Str::slug(config('app.name_en')) . '_' . auth()->id() . '&lang=' . config('app.ai_assistant_lang');
    @endphp
    <div x-data="aiAssistant(@js($aiAssistantBaseSrc))" dir="rtl" x-cloak @keydown.escape.window="handleEscape()">

        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-10 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-10 scale-95"
             :class="maximized
                ? 'inset-4 md:inset-10 z-[1000]'
                : 'bottom-24 inset-x-4 md:inset-x-auto md:left-6 md:w-[400px] h-[600px] max-h-[70vh] z-[999]'"
             class="fixed flex flex-col overflow-hidden rounded-2xl transition-all duration-300"
             style="background:var(--md-sys-color-surface);box-shadow:0 24px 80px rgba(0,0,0,.28);border:1px solid var(--md-sys-color-outline-variant);"
             role="dialog"
             aria-label="دستیار هوش مصنوعی"
             x-cloak>

            <div class="relative overflow-hidden px-3 py-2 shrink-0"
                 style="background:linear-gradient(135deg,var(--md-sys-color-primary),color-mix(in srgb,var(--md-sys-color-primary) 78%,#000 22%));border-bottom:1px solid rgba(255,255,255,.08);">
                <div class="absolute inset-0 pointer-events-none opacity-25"
                     style="background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);animation:shimmer 6s linear infinite;"></div>

                <div class="relative flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 ring-white/15"
                             style="background:rgba(255,255,255,.12);">
                            <span class="material-symbols-rounded" style="color:var(--md-sys-color-on-primary);font-size:24px;">auto_awesome</span>
                        </div>
                        <p class="text-sm font-semibold truncate" style="color:var(--md-sys-color-on-primary);">دستیار هوش مصنوعی</p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="toggleMaximized()"
                                class="w-10 h-10 shrink-0 flex items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] text-[var(--md-sys-color-on-primary)] border border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_20%,transparent)] active:scale-[0.92] transition-all duration-200 ease-out hover:-translate-y-px focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--md-sys-color-on-primary)_40%,transparent)]"
                                :aria-label="maximized ? 'کوچک کردن' : 'بزرگ کردن'"
                                :title="maximized ? 'کوچک کردن' : 'بزرگ کردن'">
                            <span x-show="!maximized" class="material-symbols-rounded text-[20px]">open_in_full</span>
                            <span x-show="maximized" class="material-symbols-rounded text-[20px]">close_fullscreen</span>
                        </button>
                        <x-ui.modals.close-button close="close()" tone="on-primary" size="lg" class="hover:-translate-y-px"/>
                    </div>
                </div>
            </div>

            <div class="flex-1 relative">
                <div x-show="!frameReady" class="absolute inset-0 flex items-center justify-center animate-pulse" style="background:var(--md-sys-color-surface-variant);">
                    <span class="material-symbols-rounded" style="font-size:40px;color:var(--md-sys-color-outline);">auto_awesome</span>
                </div>

                <template x-if="loaded">
                    <iframe
                        x-bind:src="src"
                        class="w-full h-full border-0"
                        allow="clipboard-write; fullscreen; microphone"
                        referrerpolicy="strict-origin-when-cross-origin"
                        title="دستیار هوش مصنوعی"
                        @load="frameReady = true">
                    </iframe>
                </template>
            </div>
        </div>
    </div>
@endif
