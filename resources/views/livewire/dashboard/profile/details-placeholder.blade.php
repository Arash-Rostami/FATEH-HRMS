<div class="space-y-6 animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری اطلاعات تکمیلی">
    @for($i = 0; $i < 3; $i++)
        <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
                <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
                <x-ui.loaders.skeleton.bar width="w-40" height="h-4"/>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @for($j = 0; $j < 6; $j++)
                        <div class="flex flex-col gap-1.5">
                            <x-ui.loaders.skeleton.bar width="w-24" height="h-3"/>
                            <x-ui.loaders.skeleton.bar width="w-full" height="h-10"/>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endfor
</div>
