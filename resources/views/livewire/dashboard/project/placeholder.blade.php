<div dir="rtl" class="w-full h-[calc(100dvh-60px)] md:h-[calc(100dvh-80px)] px-4 py-4 md:px-6 md:py-8" role="status" aria-label="در حال بارگذاری پروژه‌ها">
    <div class="max-w-[88rem] mx-auto h-full flex flex-col gap-4">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-7"/>

        <div class="chat-widget flex-1 min-h-0">
            <aside class="flex-shrink-0 w-full md:w-[320px] lg:w-[360px] border-l border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface)] flex flex-col">
                <div class="flex-shrink-0 px-4 pt-4 pb-3 flex flex-col gap-3">
                    <x-ui.loaders.skeleton.bar width="w-24" height="h-5"/>
                    <x-ui.loaders.skeleton.bar width="w-full" height="h-10"/>
                </div>
                <div class="flex flex-col overflow-hidden">
                    @for($i = 0; $i < 6; $i++)
                        <x-ui.loaders.skeleton.avatar-row :lines="1"/>
                    @endfor
                </div>
            </aside>

            <main class="hidden md:flex flex-1 flex-col bg-[var(--md-sys-color-background)]">
                <div class="flex-shrink-0 p-4 md:p-5 border-b border-[var(--md-sys-color-outline-variant)]/40 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-[var(--md-sys-color-surface-variant)] animate-pulse flex-shrink-0"></div>
                    <div class="flex-1 flex flex-col gap-2">
                        <x-ui.loaders.skeleton.bar width="w-1/3" height="h-4"/>
                        <x-ui.loaders.skeleton.bar width="w-1/2" height="h-3"/>
                    </div>
                </div>
                <div class="flex-1 overflow-hidden p-4 md:p-6 flex flex-col gap-4">
                    <x-ui.loaders.skeleton.bar width="w-64" height="h-9"/>
                    @for($i = 0; $i < 4; $i++)
                        <div class="flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] animate-pulse flex-shrink-0"></div>
                            <x-ui.loaders.skeleton.bar :width="$i % 2 === 0 ? 'w-2/5' : 'w-1/3'" height="h-10"/>
                        </div>
                    @endfor
                </div>
            </main>
        </div>
    </div>
</div>
