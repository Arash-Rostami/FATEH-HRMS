<div class="space-y-6 animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری درباره من">
    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)] shadow-sm">
        <x-ui.loaders.skeleton.bar width="w-full" height="h-4" class="mb-2"/>
        <x-ui.loaders.skeleton.bar width="w-2/3" height="h-4" class="mb-6"/>

        <div class="flex flex-col gap-1.5 mb-5">
            <x-ui.loaders.skeleton.bar width="w-40" height="h-3"/>
            <x-ui.loaders.skeleton.bar width="w-full" height="h-24"/>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @for($i = 0; $i < 4; $i++)
                <div class="flex flex-col gap-1.5">
                    <x-ui.loaders.skeleton.bar width="w-28" height="h-3"/>
                    <x-ui.loaders.skeleton.bar width="w-full" height="h-10"/>
                </div>
            @endfor
        </div>
    </div>
</div>
