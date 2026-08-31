<div
    dir="rtl"
    role="region"
    aria-label="در حال بارگذاری مخاطبین"
    class="w-full h-[calc(100dvh-60px)] md:h-[calc(100dvh-80px)] relative px-4 py-4 md:px-6 md:py-8 overflow-hidden animate-fade">

    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col gap-4">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-7"/>

        <div class="chat-widget flex-1 min-h-0 flex gap-0">
            <aside class="hidden md:flex flex-shrink-0 w-full md:w-[320px] lg:w-[360px] flex-col gap-2 border-l border-[var(--md-sys-color-outline-variant)]/30 pl-2">
                <div class="px-2 pb-2">
                    <x-ui.loaders.skeleton.bar width="w-full" height="h-10"/>
                </div>
                @for($i = 0; $i < 7; $i++)
                    <x-ui.loaders.skeleton.avatar-row/>
                @endfor
            </aside>

            <main class="flex-1 flex flex-col overflow-hidden bg-[var(--md-sys-color-background)] px-4 gap-3 pt-2">
                <div class="flex flex-col gap-3">
                    <x-ui.loaders.skeleton.bar width="w-1/3" height="h-4"/>
                    <x-ui.loaders.skeleton.bar width="w-1/2" height="h-9"/>
                    <div class="flex justify-end">
                        <x-ui.loaders.skeleton.bar width="w-2/5" height="h-9"/>
                    </div>
                    <x-ui.loaders.skeleton.bar width="w-2/3" height="h-9"/>
                    <div class="flex justify-end">
                        <x-ui.loaders.skeleton.bar width="w-1/3" height="h-9"/>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
