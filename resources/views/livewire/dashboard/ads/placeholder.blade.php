<div class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری فرصت‌های شغلی">
    <div class="max-w-[88rem] mx-auto page-wrapper">
        <div class="flex items-center justify-between mb-6">
            <x-ui.loaders.skeleton.bar width="w-48" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-20" height="h-8"/>
        </div>

        <div class="flex flex-wrap items-center gap-2 mb-6">
            <x-ui.loaders.skeleton.bar width="w-24" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-28" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-64" height="h-8"/>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 pb-12">
            @for($i = 0; $i < 6; $i++)
                <x-ui.loaders.skeleton.card :lines="2" class="min-h-[380px]"/>
            @endfor
        </div>
    </div>
</div>
