<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    
     role="status" aria-label="در حال بارگذاری اختیارات سازمانی">
    <div class="max-w-[88rem] mx-auto page-wrapper">
        <div class="mb-8">
            <x-ui.loaders.skeleton.bar width="w-48" height="h-7"/>
        </div>

        <div class="flex gap-2 mb-6">
            <x-ui.loaders.skeleton.bar width="flex-1" height="h-11"/>
            <x-ui.loaders.skeleton.bar width="w-40" height="h-11"/>
        </div>

        <div class="flex items-center justify-between gap-3 mb-6">
            <div class="flex gap-2">
                <x-ui.loaders.skeleton.bar width="w-32" height="h-9"/>
                <x-ui.loaders.skeleton.bar width="w-32" height="h-9"/>
            </div>
            <x-ui.loaders.skeleton.bar width="w-24" height="h-9"/>
        </div>

        <div class="flex flex-col gap-3">
            @for($i = 0; $i < 8; $i++)
                <x-ui.loaders.skeleton.avatar-row :lines="2" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 py-3"/>
            @endfor
        </div>
    </div>
</div>
