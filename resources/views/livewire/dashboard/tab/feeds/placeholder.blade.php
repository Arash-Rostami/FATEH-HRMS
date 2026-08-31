<div class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] h-screen overflow-hidden flex flex-col gap-6" dir="rtl" role="status" aria-label="در حال بارگذاری اخبار و فیدها">
    <div class="flex items-center justify-between">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-8"/>
        <x-ui.loaders.skeleton.bar width="w-24" height="h-8"/>
    </div>

    <div class="flex-1 hidden md:flex items-center gap-6 px-[10%]">
        @for($i = 0; $i < 3; $i++)
            <div class="flex-shrink-0 w-[400px] h-full flex flex-col bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <x-ui.loaders.skeleton.bar width="w-10" height="h-10"/>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <x-ui.loaders.skeleton.bar width="w-28" height="h-3.5"/>
                        <x-ui.loaders.skeleton.bar width="w-16" height="h-2.5"/>
                    </div>
                </div>
                <x-ui.loaders.skeleton.bar width="w-full" height="h-32" class="mb-4"/>
                <div class="flex flex-col gap-2 mb-auto">
                    <x-ui.loaders.skeleton.bar width="w-full" height="h-3"/>
                    <x-ui.loaders.skeleton.bar width="w-4/5" height="h-3"/>
                </div>
                <div class="flex items-center gap-2 pt-4 mt-4 border-t border-[var(--md-sys-color-outline-variant)]/20">
                    <x-ui.loaders.skeleton.bar width="w-16" height="h-6"/>
                    <x-ui.loaders.skeleton.bar width="w-24" height="h-6"/>
                </div>
            </div>
        @endfor
    </div>

    <div class="flex-1 md:hidden flex flex-col gap-3 overflow-hidden">
        @for($i = 0; $i < 2; $i++)
            <div class="flex flex-col bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <x-ui.loaders.skeleton.bar width="w-9" height="h-9"/>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <x-ui.loaders.skeleton.bar width="w-24" height="h-3"/>
                        <x-ui.loaders.skeleton.bar width="w-14" height="h-2.5"/>
                    </div>
                </div>
                <x-ui.loaders.skeleton.bar width="w-full" height="h-28" class="mb-3"/>
                <x-ui.loaders.skeleton.bar width="w-3/4" height="h-3"/>
            </div>
        @endfor
    </div>
</div>
