<div class="space-y-4" role="status" aria-label="در حال بارگذاری جزئیات پیشنهاد">
    <div class="rounded-2xl overflow-hidden shadow-sm bg-[var(--md-sys-color-surface)] p-5 md:p-6 flex flex-col gap-3">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-3" class="!bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_45%,var(--md-sys-color-surface-variant))]"/>

        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl shrink-0 bg-[var(--md-sys-color-primary-container)] animate-pulse"></div>

            <div class="flex-1 min-w-0 flex flex-col gap-2">
                <x-ui.loaders.skeleton.bar width="w-2/3" height="h-5" class="!bg-[color-mix(in_srgb,var(--md-sys-color-primary)_40%,var(--md-sys-color-surface-variant))]"/>
                <x-ui.loaders.skeleton.bar width="w-full" height="h-3" class="!bg-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,var(--md-sys-color-surface-variant))]"/>
                <x-ui.loaders.skeleton.bar width="w-4/5" height="h-3" class="!bg-[color-mix(in_srgb,var(--md-sys-color-primary)_25%,var(--md-sys-color-surface-variant))]"/>
            </div>
        </div>
    </div>

    <x-ui.loaders.skeleton.card :lines="2" class="!bg-[var(--md-sys-color-primary-container)] [&>:first-child]:!bg-[color-mix(in_srgb,var(--md-sys-color-primary)_35%,var(--md-sys-color-surface-variant))]"/>
    <x-ui.loaders.skeleton.card :lines="2" class="!bg-[var(--md-sys-color-tertiary-container)] [&>:first-child]:!bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_35%,var(--md-sys-color-surface-variant))]"/>
</div>
