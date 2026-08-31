<div class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6" dir="rtl" role="status" aria-label="در حال بارگذاری گالری">
    <div class="flex items-center justify-between">
        <x-ui.loaders.skeleton.bar width="w-32" height="h-8"/>
        <x-ui.loaders.skeleton.bar width="w-24" height="h-8"/>
    </div>

    <div class="flex-1 hidden md:flex items-center gap-6 px-[10%]">
        @for($i = 0; $i < 3; $i++)
            <div class="flex-shrink-0 w-[400px] h-full flex flex-col bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex flex-col gap-1.5">
                        <x-ui.loaders.skeleton.bar width="w-32" height="h-3.5"/>
                        <x-ui.loaders.skeleton.bar width="w-20" height="h-2.5"/>
                    </div>
                    <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
                </div>
                <div class="flex-1 relative flex items-center justify-center min-h-[168px]">
                    <x-ui.loaders.skeleton.bar width="w-40" height="h-40" class="absolute -rotate-6"/>
                    <x-ui.loaders.skeleton.bar width="w-40" height="h-40" class="absolute rotate-3 translate-x-6"/>
                </div>
                <x-ui.loaders.skeleton.bar width="w-3/5" height="h-3" class="mt-3"/>
            </div>
        @endfor
    </div>

    <div class="flex-1 md:hidden grid grid-cols-2 gap-2 content-start overflow-hidden">
        @for($i = 0; $i < 6; $i++)
            <div class="aspect-square rounded-2xl overflow-hidden bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 p-2 flex flex-col gap-2">
                <x-ui.loaders.skeleton.bar width="w-full" height="h-full"/>
            </div>
        @endfor
    </div>
</div>
