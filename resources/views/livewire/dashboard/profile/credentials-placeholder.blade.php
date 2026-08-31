<div class="space-y-5 animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری دسترسی و امنیتی">
    <div class="rounded-2xl bg-[var(--md-sys-color-error-container)]/40 border border-[var(--md-sys-color-error)]/20 p-5">
        <div class="flex gap-4 items-center">
            <x-ui.loaders.skeleton.bar width="w-10" height="h-10"/>
            <div class="flex-1 flex flex-col gap-2">
                <x-ui.loaders.skeleton.bar width="w-48" height="h-4"/>
                <x-ui.loaders.skeleton.bar width="w-3/5" height="h-3"/>
            </div>
        </div>
    </div>

    <x-ui.loaders.skeleton.bar width="w-full max-w-sm" height="h-10"/>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @for($i = 0; $i < 6; $i++)
            <x-ui.loaders.skeleton.card :lines="2" class="min-h-[120px]"/>
        @endfor
    </div>
</div>
