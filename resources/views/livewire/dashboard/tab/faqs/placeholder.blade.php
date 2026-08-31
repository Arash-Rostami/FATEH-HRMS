<div class="animate-fade relative w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] flex flex-col overflow-hidden" dir="rtl" role="status" aria-label="در حال بارگذاری پرسش‌های متداول">
    <div class="pb-0 shrink-0">
        <div class="flex items-center justify-between mb-4">
            <x-ui.loaders.skeleton.bar width="w-48" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-20" height="h-8"/>
        </div>

        <div class="flex flex-wrap items-center gap-2 mb-4">
            <x-ui.loaders.skeleton.bar width="w-64" height="h-9"/>
            <x-ui.loaders.skeleton.bar width="w-24" height="h-9"/>
            <x-ui.loaders.skeleton.bar width="w-24" height="h-9"/>
        </div>
    </div>

    <div class="flex-1 overflow-hidden p-4 md:p-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-start">
            @for($i = 0; $i < 6; $i++)
                <x-ui.loaders.skeleton.card :lines="2"/>
            @endfor
        </div>
    </div>
</div>
