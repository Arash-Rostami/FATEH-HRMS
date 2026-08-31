<div class="space-y-5 animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری مدارک و اسناد">
    <div class="rounded-2xl bg-[var(--md-sys-color-tertiary-container)]/40 border border-[var(--md-sys-color-tertiary)]/20 p-5">
        <div class="flex gap-4 items-start">
            <x-ui.loaders.skeleton.bar width="w-10" height="h-10"/>
            <div class="flex-1 flex flex-col gap-2">
                <x-ui.loaders.skeleton.bar width="w-56" height="h-4"/>
                <x-ui.loaders.skeleton.bar width="w-full" height="h-3"/>
                <x-ui.loaders.skeleton.bar width="w-4/5" height="h-3"/>
            </div>
        </div>
    </div>

    <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
            <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-44" height="h-4"/>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @for($i = 0; $i < 6; $i++)
                    <x-ui.loaders.skeleton.card :lines="1" class="min-h-[140px]"/>
                @endfor
            </div>
        </div>
    </div>
</div>
