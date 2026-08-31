<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col" dir="rtl" role="status" aria-label="در حال بارگذاری گزارشات">
    <div class="flex items-center justify-between mb-4">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-8"/>
        <x-ui.loaders.skeleton.bar width="w-24" height="h-8"/>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-6">
        <x-ui.loaders.skeleton.bar width="w-64" height="h-9"/>
        <x-ui.loaders.skeleton.bar width="w-20" height="h-9"/>
        <x-ui.loaders.skeleton.bar width="w-20" height="h-9"/>
        <x-ui.loaders.skeleton.bar width="w-20" height="h-9"/>
    </div>

    <div class="w-full hidden md:flex items-center gap-6" style="height: clamp(420px, calc(100svh - 200px), 800px);">
        @for($i = 0; $i < 4; $i++)
            <div class="shrink-0 w-[380px] h-[90%] flex flex-col rounded-3xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)]">
                <x-ui.loaders.skeleton.bar width="w-full" height="h-[60%]"/>
                <div class="flex-1 flex flex-col justify-between px-5 py-4 border-t border-[var(--md-sys-color-outline-variant)]/30">
                    <div class="flex flex-col gap-2">
                        <x-ui.loaders.skeleton.bar width="w-4/5" height="h-4"/>
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-3"/>
                        <x-ui.loaders.skeleton.bar width="w-3/5" height="h-3"/>
                    </div>
                    <div class="flex items-center justify-between pt-3 mt-3 border-t border-[var(--md-sys-color-outline-variant)]/20">
                        <x-ui.loaders.skeleton.bar width="w-20" height="h-3"/>
                        <x-ui.loaders.skeleton.bar width="w-16" height="h-7"/>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <div class="w-full flex md:hidden flex-col gap-4">
        @for($i = 0; $i < 2; $i++)
            <div class="w-full flex flex-col rounded-3xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)]">
                <x-ui.loaders.skeleton.bar width="w-full" height="h-40"/>
                <div class="flex flex-col gap-2 px-5 py-4 border-t border-[var(--md-sys-color-outline-variant)]/30">
                    <x-ui.loaders.skeleton.bar width="w-4/5" height="h-4"/>
                    <x-ui.loaders.skeleton.bar width="w-full" height="h-3"/>
                    <div class="flex items-center justify-between pt-3 mt-1 border-t border-[var(--md-sys-color-outline-variant)]/20">
                        <x-ui.loaders.skeleton.bar width="w-20" height="h-3"/>
                        <x-ui.loaders.skeleton.bar width="w-16" height="h-7"/>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>
