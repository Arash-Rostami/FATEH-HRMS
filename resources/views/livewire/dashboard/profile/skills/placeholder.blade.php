<div class="space-y-6 animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری استعدادها">
    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)] shadow-sm">
        <x-ui.loaders.skeleton.bar width="w-full" height="h-4" class="mb-2"/>
        <x-ui.loaders.skeleton.bar width="w-3/4" height="h-4" class="mb-6"/>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-ui.loaders.skeleton.bar width="w-full" height="h-11"/>
            <x-ui.loaders.skeleton.bar width="w-full" height="h-11"/>
            <div class="md:col-span-2 flex justify-end">
                <x-ui.loaders.skeleton.bar width="w-32" height="h-10"/>
            </div>
        </div>
    </div>

    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)] shadow-sm">
        <x-ui.loaders.skeleton.bar width="w-32" height="h-5" class="mb-4"/>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @for($i = 0; $i < 6; $i++)
                <x-ui.loaders.skeleton.avatar-row :lines="2" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40"/>
            @endfor
        </div>
    </div>
</div>
