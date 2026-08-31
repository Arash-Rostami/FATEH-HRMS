<div class="mt-4 flex flex-col gap-4 animate-fade" x-on:project-activity-refresh.window="$wire.refreshActivity()" role="status" aria-label="در حال بارگذاری فعالیت‌ها و نظرات">
    <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-4 flex items-center gap-2.5">
        <x-ui.loaders.skeleton.bar width="w-9" height="h-9" class="rounded-full"/>
        <x-ui.loaders.skeleton.bar width="w-full" height="h-9" class="rounded-xl"/>
        <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="rounded-xl"/>
    </div>

    <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
        <div class="flex flex-col gap-1 p-2">
            @for($i = 0; $i < 4; $i++)
                <x-ui.loaders.skeleton.avatar-row :lines="2"/>
            @endfor
        </div>
    </div>
</div>
