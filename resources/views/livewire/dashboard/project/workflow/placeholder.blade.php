<div class="animate-fade" @project-workflow-refresh.window="$wire.refreshWorkflow()" role="status" aria-label="در حال بارگذاری چرخه‌های کاری">
    <div class="flex items-center gap-2.5 p-2 mb-4 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_88%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-9" class="rounded-xl"/>
        <x-ui.loaders.skeleton.bar width="w-28" height="h-7" class="rounded-lg"/>
    </div>

    <div class="space-y-3">
        @for($i = 0; $i < 2; $i++)
            <x-ui.loaders.skeleton.card/>
        @endfor
    </div>
</div>
