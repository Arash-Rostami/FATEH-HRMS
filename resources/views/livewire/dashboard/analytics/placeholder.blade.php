<div dir="rtl" class="w-full h-full px-4 py-4 md:px-6 md:py-8" role="status" aria-label="در حال بارگذاری تحلیل‌های سازمانی">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-5 lg:gap-6">
        <x-ui.loaders.skeleton.bar width="w-48" height="h-7"/>

        <div class="flex flex-wrap gap-2">
            @for ($i = 0; $i < 4; $i++)
                <x-ui.loaders.skeleton.bar width="w-40" height="h-10"/>
            @endfor
        </div>

        <x-ui.loaders.skeleton.card :lines="1" class="min-h-[420px] flex-1"/>
    </div>
</div>
