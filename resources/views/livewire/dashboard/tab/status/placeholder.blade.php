<div class="animate-fade w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] flex flex-col gap-4" dir="rtl" role="status" aria-label="در حال بارگذاری وضعیت همکاران">
    <div class="flex items-center justify-between">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-8"/>
        <x-ui.loaders.skeleton.bar width="w-20" height="h-8"/>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <x-ui.loaders.skeleton.bar width="w-24" height="h-8"/>
        <x-ui.loaders.skeleton.bar width="w-24" height="h-8"/>
        <x-ui.loaders.skeleton.bar width="w-24" height="h-8"/>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @for($i = 0; $i < 8; $i++)
            <x-ui.loaders.skeleton.avatar-row :lines="2" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 py-4"/>
        @endfor
    </div>
</div>
