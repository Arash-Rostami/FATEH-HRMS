<div class="space-y-6 animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری آنبوردینگ">
    @for($i = 0; $i < 3; $i++)
        <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
            <div class="flex items-center gap-4 p-6 bg-[var(--md-sys-color-primary-container)]/15 border-b border-[var(--md-sys-color-outline-variant)]">
                <x-ui.loaders.skeleton.bar width="w-12" height="h-12"/>
                <div class="flex flex-col gap-1.5">
                    <x-ui.loaders.skeleton.bar width="w-40" height="h-4"/>
                    <x-ui.loaders.skeleton.bar width="w-28" height="h-3"/>
                </div>
            </div>
            <div class="p-6 flex flex-col gap-2.5">
                <x-ui.loaders.skeleton.bar width="w-full" height="h-3"/>
                <x-ui.loaders.skeleton.bar width="w-full" height="h-3"/>
                <x-ui.loaders.skeleton.bar width="w-3/5" height="h-3"/>
            </div>
        </div>
    @endfor
</div>
